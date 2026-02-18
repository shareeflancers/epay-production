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
    ThemedTextarea,
    ThemedSwitch,
    ThemedButton,
    ThemedModal
} from '../../../Components/UI';

export default function FeeFundCategoryIndex({ categories, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [deletingCategory, setDeletingCategory] = useState(null);
    const [tableData, setTableData] = useState(categories.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(categories.data);
    }, [categories.data]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        category_title: '',
        details: '',
        is_active: true,
    });

    // Handle add button click
    const handleAdd = () => {
        setEditingCategory(null);
        reset();
        setData({ category_title: '', details: '', is_active: true });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingCategory(row);
        setData({
            category_title: row.category_title || '',
            details: row.details,
            is_active: row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingCategory(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/fee-fund-categories/${deletingCategory.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingCategory(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/fee-fund-categories/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/fee-fund-categories', {
            ...filters,
            search: value,
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
        router.post('/admin/fee-fund-categories/reorder', {
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

    // Handle page change
    const handlePageChange = (page) => {
        router.get('/admin/fee-fund-categories', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/fee-fund-categories', {
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
            { key: 'category_title', label: 'Title', width: 200, sortable: true },
        { key: 'details', label: 'Category Details' },
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

        if (editingCategory) {
            put(`/admin/fee-fund-categories/${editingCategory.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/fee-fund-categories', {
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
                {/* Page Header */}
                <Box>
                    <Title order={2} mb={4}>Fee Categories</Title>
                    <Text c="dimmed" size="sm">
                        Manage fee categories for the system
                    </Text>
                </Box>

                {/* DataTable */}
                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Category"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: categories.current_page,
                        last_page: categories.last_page,
                        per_page: categories.per_page,
                        total: categories.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No fee categories found"
                    enableExport={true}
                    onReorder={handleReorder}

                />

                {/* Create/Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingCategory ? 'Edit Category' : 'Add New Category'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Category Title"
                                placeholder="Enter category title"
                                value={data.category_title}
                                onChange={(e) => setData('category_title', e.target.value)}
                                error={errors.category_title}
                                required
                            />
                            <ThemedTextarea
                                label="Category Details"
                                placeholder="Enter category details"
                                value={data.details}
                                onChange={(e) => setData('details', e.target.value)}
                                error={errors.details}
                                required
                                minRows={3}
                                autosize
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
                                    {editingCategory ? 'Update' : 'Create'}
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
                            Are you sure you want to delete the category "{deletingCategory?.details}"?
                        </Text>
                        <Text size="sm" c="dimmed">
                            This action cannot be undone.
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
