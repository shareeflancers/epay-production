import { useState, useMemo, useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
    Badge,
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

export default function InstitutionIndex({ institutions, regions, levels, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingInstitution, setEditingInstitution] = useState(null);
    const [deletingInstitution, setDeletingInstitution] = useState(null);
    const [tableData, setTableData] = useState(institutions.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(institutions.data);
    }, [institutions.data]);

    // Prepare options
    const regionOptions = useMemo(() => regions.map(r => ({ value: String(r.id), label: r.name })), [regions]);
    const levelOptions = useMemo(() => levels.map(l => ({ value: String(l.id), label: l.level })), [levels]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        region_id: '',
        level_id: '',
        is_active: true,
    });

    // Handle add button click
    const handleAdd = () => {
        setEditingInstitution(null);
        reset();
        setData({
            name: '',
            region_id: '',
            level_id: '',
            is_active: true,
        });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingInstitution(row);
        setData({
            name: row.name || '',
            region_id: String(row.region_id || ''),
            level_id: String(row.level_id || ''),
            is_active: !!row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingInstitution(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/institutions/${deletingInstitution.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingInstitution(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/institutions/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle reorder
    const handleReorder = (newItems) => {
        setTableData(newItems); // Optimistic update
        const ids = newItems.map(item => item.id);
        router.post('/admin/institutions/reorder', {
            ids: ids
        }, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                setTableData(institutions.data);
            }
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/institutions', {
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
        router.get('/admin/institutions', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/institutions', {
            ...filters,
            per_page: perPage,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle sort
    const handleSort = (column) => {
        const direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/institutions', {
            ...filters,
            sort: column,
            direction: direction,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Column definitions
    const columns = useMemo(() => [
        { key: 'name', label: 'Name', sortable: true },
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
        {
            key: 'created_at',
            label: 'Created At',
            width: 150,
            render: (value) => value ? new Date(value).toLocaleDateString() : '-'
        },
    ], []);

    // Submit form
    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingInstitution) {
            put(`/admin/institutions/${editingInstitution.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/institutions', {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        }
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Institutions</Title>
                    <Text c="dimmed" size="sm">
                        Manage institutions
                    </Text>
                </Box>

                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Institution"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: institutions.current_page,
                        last_page: institutions.last_page,
                        per_page: institutions.per_page,
                        total: institutions.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No institutions found"
                    enableExport={true}
                    onReorder={handleReorder}
                    onSort={handleSort}
                    sortColumn={filters.sort}
                    sortDirection={filters.direction}
                />

                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingInstitution ? 'Edit Institution' : 'Add New Institution'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Name"
                                placeholder="Enter institution name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />

                            <ThemedSelect
                                label="Region"
                                placeholder="Select region"
                                data={regionOptions}
                                value={data.region_id}
                                onChange={(val) => setData('region_id', val)}
                                error={errors.region_id}
                                required
                                searchable
                                styles={{ input: { cursor: 'pointer' } }}
                            />

                            <ThemedSelect
                                label="Level"
                                placeholder="Select level"
                                data={levelOptions}
                                value={data.level_id}
                                onChange={(val) => setData('level_id', val)}
                                error={errors.level_id}
                                required
                                searchable
                                styles={{ input: { cursor: 'pointer' } }}
                            />

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
                                    {editingInstitution ? 'Update' : 'Create'}
                                </ThemedButton>
                            </Group>
                        </Stack>
                    </form>
                </ThemedModal>

                <ThemedModal
                    opened={deleteOpened}
                    onClose={closeDelete}
                    title="Confirm Delete"
                    centered
                    size="sm"
                >
                    <Stack gap="md">
                        <Text>
                            Are you sure you want to delete the institution "{deletingInstitution?.name}"?
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
