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
    ThemedModal
} from '../../../Components/UI';

export default function InducteeIndex({ consumers, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingInductee, setEditingInductee] = useState(null);
    const [deletingInductee, setDeletingInductee] = useState(null);
    const [tableData, setTableData] = useState(consumers.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(consumers.data);
    }, [consumers.data]);

    // Initialize form
    const { data, setData, put, processing, errors, reset } = useForm({
        name: '',
        father_or_guardian_name: '',
    });

    // Handle view/edit button click
    const handleEdit = (row) => {
        setEditingInductee(row);
        setData({
            name: row.name || '',
            father_or_guardian_name: row.father_or_guardian_name || '',
        });
        open();
    };

    // Handle update submit
    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/consumers/${editingInductee.consumer_id}`, {
            onSuccess: () => {
                close();
                setEditingInductee(null);
                reset();
            },
        });
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingInductee(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/consumers/${deletingInductee.consumer_id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingInductee(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/consumers/${row.consumer_id}/status`, {
            is_active: checked
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/consumers/inductee', {
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
        router.get('/admin/consumers/inductee', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/consumers/inductee', {
            ...filters,
            per_page: perPage,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Column definitions for inductees
    const columns = useMemo(() => [
        {
            key: 'consumer_number',
            label: 'Consumer Number',
            width: 120
        },
        {
            key: 'identification_number',
            label: 'ID Number',
            width: 120
        },
        {
            key: 'name',
            label: 'Inductee Name',
            width: 180
        },
        {
            key: 'father_or_guardian_name',
            label: 'Father/Guardian',
            width: 180
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
            width: 120,
            render: (value) => value ? new Date(value).toLocaleDateString() : '-'
        },
    ], []);

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Inductees</Title>
                    <Text c="dimmed" size="sm">
                        Manage inductee consumers in the system
                    </Text>
                </Box>

                {/* DataTable */}
                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={false}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    searchPlaceholder="Search ..."
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: consumers.current_page,
                        last_page: consumers.last_page,
                        per_page: consumers.per_page,
                        total: consumers.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No inductees found"
                    enableExport={true}
                />

                {/* Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={() => {
                        close();
                        setEditingInductee(null);
                        reset();
                    }}
                    title="Edit Inductee"
                    centered
                    size="lg"
                >
                    {editingInductee && (
                        <form onSubmit={handleSubmit}>
                            <Stack gap="md">
                                <Group grow>
                                    <Box>
                                        <Text size="xs" c="dimmed" fw={500}>ID Number</Text>
                                        <Text size="sm">{editingInductee.identification_number}</Text>
                                    </Box>
                                    <Box>
                                        <Text size="xs" c="dimmed" fw={500}>Consumer ID</Text>
                                        <Text size="sm">{editingInductee.consumer_id}</Text>
                                    </Box>
                                </Group>

                                <ThemedInput
                                    label="Inductee Name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                    required
                                />

                                <ThemedInput
                                    label="Father/Guardian Name"
                                    value={data.father_or_guardian_name}
                                    onChange={(e) => setData('father_or_guardian_name', e.target.value)}
                                    error={errors.father_or_guardian_name}
                                />

                                <Group justify="flex-end" mt="md">
                                    <ThemedButton
                                        themeVariant="subtle"
                                        onClick={() => {
                                            close();
                                            setEditingInductee(null);
                                            reset();
                                        }}
                                        disabled={processing}
                                    >
                                        Cancel
                                    </ThemedButton>
                                    <ThemedButton
                                        type="submit"
                                        themeVariant="primary"
                                        loading={processing}
                                    >
                                        Save Changes
                                    </ThemedButton>
                                </Group>
                            </Stack>
                        </form>
                    )}
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
                            Are you sure you want to delete inductee "{deletingInductee?.name}"?
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
