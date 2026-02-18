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

export default function LevelIndex({ levels, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingLevel, setEditingLevel] = useState(null);
    const [deletingLevel, setDeletingLevel] = useState(null);
    const [tableData, setTableData] = useState(levels.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(levels.data);
    }, [levels.data]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        level: '',
        is_active: true,
    });

    // Handle add button click
    const handleAdd = () => {
        setEditingLevel(null);
        reset();
        setData({ level: '', is_active: true });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingLevel(row);
        setData({
            level: row.level || '',
            is_active: !!row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingLevel(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/levels/${deletingLevel.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingLevel(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/levels/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/levels', {
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
        router.get('/admin/levels', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/levels', {
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
        router.post('/admin/levels/reorder', {
            ids: ids
        }, {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Revert on error if needed
                setTableData(levels.data);
            }
        });
    };

    // Column definitions
    const columns = useMemo(() => [
        { key: 'level', label: 'Level', sortable: true },
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

        if (editingLevel) {
            put(`/admin/levels/${editingLevel.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/levels', {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        }
    };

    // Handle sort
    const handleSort = (column) => {
        const direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/levels', {
            ...filters,
            sort: column,
            direction: direction,
            page: 1, // Reset to first page on sort
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Levels</Title>
                    <Text c="dimmed" size="sm">
                        Manage updated levels
                    </Text>
                </Box>

                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Level"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: levels.current_page,
                        last_page: levels.last_page,
                        per_page: levels.per_page,
                        total: levels.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No levels found"
                    enableExport={true}
                    onSort={handleSort}
                    sortColumn={filters.sort}
                    sortDirection={filters.direction}
                    onReorder={handleReorder}
                />

                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingLevel ? 'Edit Level' : 'Add New Level'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Level"
                                placeholder="Enter level name"
                                value={data.level}
                                onChange={(e) => setData('level', e.target.value)}
                                error={errors.level}
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
                                    {editingLevel ? 'Update' : 'Create'}
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
                            Are you sure you want to delete the level "{deletingLevel?.level}"?
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
