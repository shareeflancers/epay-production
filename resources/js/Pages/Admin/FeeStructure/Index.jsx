import { useState, useMemo, useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
    SimpleGrid,
    NumberInput,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { adminNavItems } from '../../../config/navigation';
import {
    ThemedInput,
    ThemedSwitch,
    ThemedButton,
    ThemedModal,
    ThemedSelect
} from '../../../Components/UI';

export default function FeeStructureIndex({ structures, categories, regions, classes, headGroups, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingStructure, setEditingStructure] = useState(null);
    const [deletingStructure, setDeletingStructure] = useState(null);
    const [tableData, setTableData] = useState(structures.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(structures.data);
    }, [structures.data]);

    // Prepare category options for select
    const categoryOptions = useMemo(() => {
        return categories.map(cat => ({
            value: String(cat.id),
            label: cat.category_title
        }));
    }, [categories]);

    // Prepare region options
    const regionOptions = useMemo(() => {
        return regions.map(r => ({
            value: String(r.id),
            label: r.name
        }));
    }, [regions]);

    // Prepare class options
    const classOptions = useMemo(() => {
        return classes.map(c => ({
            value: String(c.id),
            label: c.name
        }));
    }, [classes]);

    // Prepare head group options
    const headGroupOptions = useMemo(() => {
        return headGroups.map(hg => ({
            value: String(hg.id),
            label: hg.head_identifier || hg.fee_head.join(', ')
        }));
    }, [headGroups]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        region_id: '',
        school_class_id: '',
        fee_fund_category_id: '',
        fee_fund_head_id: '',
        fee_head_amounts: {},
        total: 0,
        is_active: true,
    });

    // Find selected head group details
    const selectedHeadGroup = useMemo(() => {
        return headGroups.find(hg => String(hg.id) === String(data.fee_fund_head_id));
    }, [data.fee_fund_head_id, headGroups]);

    // Calculate total whenever fee components change
    useEffect(() => {
        const sum = Object.values(data.fee_head_amounts).reduce((acc, curr) => acc + (parseFloat(curr) || 0), 0);
        if (data.total !== sum) {
            setData(prev => ({ ...prev, total: sum }));
        }
    }, [data.fee_head_amounts]);

    // Handle add button click
    const handleAdd = () => {
        setEditingStructure(null);
        reset();
        setData({
            region_id: '',
            school_class_id: '',
            fee_fund_category_id: '',
            fee_fund_head_id: '',
            fee_head_amounts: {},
            total: 0,
            is_active: true,
        });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingStructure(row);
        setData({
            region_id: String(row.region_id || ''),
            school_class_id: String(row.school_class_id || ''),
            fee_fund_category_id: String(row.fee_fund_category_id || ''),
            fee_fund_head_id: String(row.fee_fund_head_id || ''),
            fee_head_amounts: row.fee_head_amounts || {},
            total: parseFloat(row.total) || 0,
            is_active: !!row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingStructure(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/fee-structure/${deletingStructure.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingStructure(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/fee-structure/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/fee-structure', {
            ...filters,
            search: value,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle page change
    const handlePageChange = (page) => {
        router.get('/admin/fee-structure', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/fee-structure', {
            ...filters,
            per_page: perPage,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle reorder
    const handleReorder = (newItems) => {
        setTableData(newItems); // Optimistic update
        const ids = newItems.map(item => item.id);
        router.post('/admin/fee-fund-heads/reorder', {
            ids: ids
        }, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                setTableData(heads.data);
            }
        });
    };

    // Column definitions
    const columns = useMemo(() => [
        {
            key: 'region_name',
            label: 'Region',
            sortable: true,
            render: (_, row) => row.region?.name || '-'
        },
        {
            key: 'class_name',
            label: 'Class',
            sortable: true,
            render: (_, row) => row.schoolClass?.name || row.school_class?.name || '-'
        },
        {
            key: 'category_title',
            label: 'Category',
            sortable: true,
            sortKey: 'category_title',
            render: (_, row) => row.feeFundCategory?.category_title || row.fee_fund_category?.category_title || '-'
        },
        {
            key: 'fee_fund_head',
            label: 'Head Group',
            width: 250,
            render: (_, row) => row.fee_fund_head?.head_identifier || '-'
        },
        {
            key: 'total',
            label: 'Total Fee',
            sortable: true,
            render: (value) => { let totalFee = parseFloat(value).toLocaleString(); return totalFee; }
        },
        {
            key: 'is_active',
            label: 'Status',
            width: 100,
            render: (value, row) => (
                <div onClick={(e) => e.stopPropagation()}>
                    <ThemedSwitch
                        checked={!!value}
                        onChange={(e) => handleStatusToggle(row, e.currentTarget.checked)}
                        size="sm"
                    />
                </div>
            )
        },
    ], []);

    // Submit form
    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingStructure) {
            put(`/admin/fee-structure/${editingStructure.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/fee-structure', {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        }
    };

    // Helper to render fee input
    const renderFeeInput = (key, label) => (
        <ThemedInput
            label={label}
            type="number"
            value={data[key]}
            onChange={(e) => setData(key, e.target.value)}
            error={errors[key]}
            min={0}
            step="0.01"
        />
    );

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                {/* Page Header */}
                <Box>
                    <Title order={2} mb={4}>Fee Structure</Title>
                    <Text c="dimmed" size="sm">
                        Manage fee structures and amounts
                    </Text>
                </Box>

                {/* DataTable */}
                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Fee Structure"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: structures.current_page,
                        last_page: structures.last_page,
                        per_page: structures.per_page,
                        total: structures.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No fee structures found"
                    enableExport={true}
                    onReorder={handleReorder}
                />

                {/* Create/Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingStructure ? 'Edit Fee Structure' : 'Add New Fee Structure'}
                    centered
                    size="xl"
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <SimpleGrid cols={2}>
                                <ThemedSelect
                                    label="Region"
                                    placeholder="Select region"
                                    data={regionOptions}
                                    value={data.region_id}
                                    onChange={(val) => setData('region_id', val)}
                                    error={errors.region_id}
                                    required
                                    searchable
                                    styles={{
                                        input: { cursor: 'pointer' }
                                    }}
                                />
                                <ThemedSelect
                                    label="Class"
                                    placeholder="Select class"
                                    data={classOptions}
                                    value={data.school_class_id}
                                    onChange={(val) => setData('school_class_id', val)}
                                    error={errors.school_class_id}
                                    required
                                    searchable
                                    styles={{
                                        input: { cursor: 'pointer' }
                                    }}
                                />
                            </SimpleGrid>

                            <ThemedSelect
                                label="Fee Category"
                                placeholder="Select category"
                                data={categoryOptions}
                                value={data.fee_fund_category_id}
                                onChange={(val) => setData('fee_fund_category_id', val)}
                                error={errors.fee_fund_category_id}
                                required
                            />

                            <ThemedSelect
                                label="Fee Head Group"
                                placeholder="Select head group"
                                data={headGroupOptions}
                                value={data.fee_fund_head_id}
                                onChange={(val) => {
                                    setData(prev => ({
                                        ...prev,
                                        fee_fund_head_id: val,
                                        fee_head_amounts: {} // Reset amounts when group changes
                                    }));
                                }}
                                error={errors.fee_fund_head_id}
                                required
                                searchable
                            />

                            {selectedHeadGroup && (
                                <>
                                    <Text fw={600} size="sm" mt="xs">Fee Details for {selectedHeadGroup.head_identifier || 'Selected Group'}</Text>
                                    <SimpleGrid cols={3}>
                                        {selectedHeadGroup.fee_head.map((head) => (
                                            <ThemedInput
                                                key={head}
                                                label={head}
                                                type="number"
                                                value={data.fee_head_amounts[head] || 0}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    setData(prev => ({
                                                        ...prev,
                                                        fee_head_amounts: {
                                                            ...prev.fee_head_amounts,
                                                            [head]: val
                                                        }
                                                    }));
                                                }}
                                                min={0}
                                                step="0.01"
                                            />
                                        ))}
                                    </SimpleGrid>
                                </>
                            )}

                            <Group grow>
                                <ThemedInput
                                    label="Total Amount"
                                    value={data.total}
                                    readOnly
                                    description="Auto-calculated"
                                    disabled
                                />
                            </Group>

                            <ThemedSwitch
                                label="Active"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.currentTarget.checked)}
                            />

                            <Group justify="flex-end" mt="md">
                                <ThemedButton
                                    themeVariant="subtle"
                                    onClick={close}
                                >
                                    Cancel
                                </ThemedButton>
                                <ThemedButton
                                    type="submit"
                                    loading={processing}
                                    themeVariant="primary"
                                >
                                    {editingStructure ? 'Update' : 'Create'}
                                </ThemedButton>
                            </Group>
                        </Stack>
                    </form>
                </ThemedModal>

                {/* Delete Confirmation Modal */}
                <ThemedModal
                    opened={deleteOpened}
                    onClose={closeDelete}
                    title="Confirm Delete"
                    centered
                    size="sm"
                >
                    <Stack gap="md">
                        <Text>
                            Are you sure you want to delete this fee structure?
                        </Text>
                        <Text size="sm" c="dimmed">
                            Region: {deletingStructure?.region?.name}, Class: {deletingStructure?.schoolClass?.name || deletingStructure?.school_class?.name}
                        </Text>
                        <Group justify="flex-end" mt="md">
                            <ThemedButton themeVariant="subtle" onClick={closeDelete}>
                                Cancel
                            </ThemedButton>
                            <ThemedButton
                                themeVariant="danger"
                                onClick={confirmDelete}
                            >
                                Delete
                            </ThemedButton>
                        </Group>
                    </Stack>
                </ThemedModal>
            </Stack>
        </AdminLayout>
    );
}
