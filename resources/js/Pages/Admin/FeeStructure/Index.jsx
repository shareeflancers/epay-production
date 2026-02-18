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

export default function FeeStructureIndex({ structures, categories, regions, levels, filters }) {
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

    // Prepare level options
    const levelOptions = useMemo(() => {
        return levels.map(l => ({
            value: String(l.id),
            label: l.level
        }));
    }, [levels]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        region_id: '',
        level_id: '',
        fee_fund_category_id: '',
        admission_fee: 0,
        slc: 0,
        tution_fee: 0,
        idf: 0,
        exam_fee: 0,
        it_fee: 0,
        csf: 0,
        rdf: 0,
        cdf: 0,
        security_fund: 0,
        bs_fund: 0,
        prep_fund: 0,
        donation_fund: 0,
        total: 0,
        is_active: true,
    });

    // Calculate total whenever fee components change
    useEffect(() => {
        const fees = [
            'admission_fee', 'slc', 'tution_fee', 'idf', 'exam_fee',
            'it_fee', 'csf', 'rdf', 'cdf', 'security_fund',
            'bs_fund', 'prep_fund', 'donation_fund'
        ];

        const sum = fees.reduce((acc, curr) => acc + (parseFloat(data[curr]) || 0), 0);

        // Only update if total is different to avoid infinite loops if we were listening to total
        if (data.total !== sum) {
            setData(prev => ({ ...prev, total: sum }));
        }
    }, [
        data.admission_fee, data.slc, data.tution_fee, data.idf, data.exam_fee,
        data.it_fee, data.csf, data.rdf, data.cdf, data.security_fund,
        data.bs_fund, data.prep_fund, data.donation_fund
    ]);

    // Handle add button click
    const handleAdd = () => {
        setEditingStructure(null);
        reset();
        setData({
            region_id: '',
            level_id: '',
            fee_fund_category_id: '',
            admission_fee: 0,
            slc: 0,
            tution_fee: 0,
            idf: 0,
            exam_fee: 0,
            it_fee: 0,
            csf: 0,
            rdf: 0,
            cdf: 0,
            security_fund: 0,
            bs_fund: 0,
            prep_fund: 0,
            donation_fund: 0,
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
            level_id: String(row.level_id || ''),
            fee_fund_category_id: String(row.fee_fund_category_id || ''),
            admission_fee: parseFloat(row.admission_fee) || 0,
            slc: parseFloat(row.slc) || 0,
            tution_fee: parseFloat(row.tution_fee) || 0,
            idf: parseFloat(row.idf) || 0,
            exam_fee: parseFloat(row.exam_fee) || 0,
            it_fee: parseFloat(row.it_fee) || 0,
            csf: parseFloat(row.csf) || 0,
            rdf: parseFloat(row.rdf) || 0,
            cdf: parseFloat(row.cdf) || 0,
            security_fund: parseFloat(row.security_fund) || 0,
            bs_fund: parseFloat(row.bs_fund) || 0,
            prep_fund: parseFloat(row.prep_fund) || 0,
            donation_fund: parseFloat(row.donation_fund) || 0,
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

    // Column definitions
    const columns = useMemo(() => [
        {
            key: 'region_name',
            label: 'Region',
            sortable: true,
            render: (_, row) => row.region?.name || '-'
        },
        {
            key: 'level_name',
            label: 'Level',
            sortable: true,
            render: (_, row) => row.level?.level || '-'
        },
        {
            key: 'category_title',
            label: 'Category',
            sortable: true,
            sortKey: 'category_title',
            render: (_, row) => row.feeFundCategory?.category_title || row.fee_fund_category?.category_title || '-'
        },
        {
            key: 'total',
            label: 'Total Fee',
            sortable: true,
            render: (value) => <Text fw={500}>{parseFloat(value).toLocaleString()}</Text>
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
                                    label="Institution Level"
                                    placeholder="Select institution level"
                                    data={levelOptions}
                                    value={data.level_id}
                                    onChange={(val) => setData('level_id', val)}
                                    error={errors.level_id}
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

                            <Text fw={600} size="sm" mt="xs">Fee Details</Text>

                            <SimpleGrid cols={3}>
                                {renderFeeInput('admission_fee', 'Admission Fee')}
                                {renderFeeInput('tution_fee', 'Tuition Fee')}
                                {renderFeeInput('slc', 'SLC')}
                                {renderFeeInput('idf', 'IDF')}
                                {renderFeeInput('exam_fee', 'Exam Fee')}
                                {renderFeeInput('it_fee', 'IT Fee')}
                                {renderFeeInput('csf', 'CSF')}
                                {renderFeeInput('rdf', 'RDF')}
                                {renderFeeInput('cdf', 'CDF')}
                                {renderFeeInput('security_fund', 'Security Fund')}
                                {renderFeeInput('bs_fund', 'BS Fund')}
                                {renderFeeInput('prep_fund', 'Prep Fund')}
                                {renderFeeInput('donation_fund', 'Donation Fund')}
                            </SimpleGrid>

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
                            Region: {deletingStructure?.region?.name}, Level: {deletingStructure?.level?.level}
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
