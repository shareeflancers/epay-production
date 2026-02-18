import { useState, useMemo, useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { adminNavItems } from '../../../config/navigation';
import {
    ThemedInput,
    ThemedSwitch,
    ThemedButton,
    ThemedModal
} from '../../../Components/UI';

export default function RegionIndex({ regions, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingRegion, setEditingRegion] = useState(null);
    const [deletingRegion, setDeletingRegion] = useState(null);
    const [tableData, setTableData] = useState(regions.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(regions.data);
    }, [regions.data]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        is_active: true,
    });

    // Handle add button click
    const handleAdd = () => {
        setEditingRegion(null);
        reset();
        setData({ name: '', is_active: true });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingRegion(row);
        setData({
            name: row.name || '',
            is_active: !!row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingRegion(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/regions/${deletingRegion.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingRegion(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/regions/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/regions', {
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
        router.get('/admin/regions', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/regions', {
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
        router.post('/admin/regions/reorder', {
            ids: ids
        }, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Revert on error if needed, or just let next page load fix it
                setTableData(categories.data);
            }
        });
    };

    // Column definitions
    const columns = useMemo(() => [
        { key: 'name', label: 'Name', sortable: true },
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

        if (editingRegion) {
            put(`/admin/regions/${editingRegion.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/regions', {
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
                    <Title order={2} mb={4}>Regions</Title>
                    <Text c="dimmed" size="sm">
                        Manage regions
                    </Text>
                </Box>

                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Region"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: regions.current_page,
                        last_page: regions.last_page,
                        per_page: regions.per_page,
                        total: regions.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No regions found"
                    enableExport={true}
                    onReorder={handleReorder}
                />

                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingRegion ? 'Edit Region' : 'Add New Region'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Name"
                                placeholder="Enter region name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
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
                                    {editingRegion ? 'Update' : 'Create'}
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
                            Are you sure you want to delete the region "{deletingRegion?.name}"?
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
