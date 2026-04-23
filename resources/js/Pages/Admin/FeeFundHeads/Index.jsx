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
    ThemedTagsInput
} from '../../../Components/UI';

export default function FeeFundHeadsIndex({ heads, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingHead, setEditingHead] = useState(null);
    const [deletingHead, setDeletingHead] = useState(null);
    const [tableData, setTableData] = useState(heads.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(heads.data);
    }, [heads.data]);

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm({
        head_identifier: '',
        fee_head: [],
        is_active: true,
    });

    // Handle add button click
    const handleAdd = () => {
        setEditingHead(null);
        reset();
        setData({ head_identifier: '', fee_head: [], is_active: true });
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingHead(row);
        setData({
            head_identifier: row.head_identifier || '',
            fee_head: Array.isArray(row.fee_head) ? row.fee_head : [],
            is_active: row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingHead(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/fee-fund-heads/${deletingHead.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingHead(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/fee-fund-heads/${row.id}/status`, {
            ...row,
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/fee-fund-heads', {
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

    // Handle page change
    const handlePageChange = (page) => {
        router.get('/admin/fee-fund-heads', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/fee-fund-heads', {
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
            key: 'head_identifier',
            label: 'Title',
            width: 250,
            sortable: true,
            render: (_, row) => row.head_identifier || '-'
        },
        {
            key: 'fee_head',
            label: 'Fee Heads',
            render: (value) => (
                <Group gap="xs">
                    {Array.isArray(value) ? value.map((head, idx) => (
                        <Badge key={idx} variant="light" color="blue">
                            {head}
                        </Badge>
                    )) : value}
                </Group>
            )
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

        if (editingHead) {
            put(`/admin/fee-fund-heads/${editingHead.id}`, {
                onSuccess: () => {
                    close();
                    reset();
                },
            });
        } else {
            post('/admin/fee-fund-heads', {
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
                    <Title order={2} mb={4}>Fee Fund Heads</Title>
                    <Text c="dimmed" size="sm">
                        Manage allowed fee heads (comma separated)
                    </Text>
                </Box>

                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Fee Head"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: heads.current_page,
                        last_page: heads.last_page,
                        per_page: heads.per_page,
                        total: heads.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No fee heads found"
                    enableExport={true}
                    onReorder={handleReorder}
                />

                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingHead ? 'Edit Fee Head' : 'Add New Fee Head'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Group Identifier"
                                placeholder="e.g. Regular Fees"
                                value={data.head_identifier}
                                onChange={(e) => setData('head_identifier', e.target.value)}
                                error={errors.head_identifier}
                                required
                            />
                            <ThemedTagsInput
                                label="Fee Heads"
                                description="Press Enter or type a comma to add a head"
                                placeholder="e.g. Admission Fee"
                                value={data.fee_head}
                                onChange={(val) => setData('fee_head', val)}
                                error={errors.fee_head}
                                required
                                clearable
                                splitChars={[',', ' ', '|']}
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
                                    {editingHead ? 'Update' : 'Create'}
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
                            Are you sure you want to delete this fee head entry?
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
