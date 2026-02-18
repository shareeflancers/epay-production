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

export default function InstitutionIndex({ consumers, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingInstitution, setEditingInstitution] = useState(null);
    const [deletingInstitution, setDeletingInstitution] = useState(null);
    const [tableData, setTableData] = useState(consumers.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(consumers.data);
    }, [consumers.data]);

    // Initialize form
    const { data, setData, put, processing, errors, reset } = useForm({
        institution_name: '',
        institution_level: '',
        region_name: '',
    });

    // Handle view/edit button click
    const handleEdit = (row) => {
        setEditingInstitution(row);
        setData({
            institution_name: row.institution_name || '',
            institution_level: row.institution_level || '',
            region_name: row.region_name || '',
        });
        open();
    };

    // Handle update submit
    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/consumers/${editingInstitution.consumer_id}`, {
            onSuccess: () => {
                close();
                setEditingInstitution(null);
                reset();
            },
        });
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingInstitution(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/consumers/${deletingInstitution.consumer_id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingInstitution(null);
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
        router.get('/admin/consumers/institution', {
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
        router.get('/admin/consumers/institution', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/consumers/institution', {
            ...filters,
            per_page: perPage,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Column definitions for institutions
    const columns = useMemo(() => [
        {
            key: 'consumer_number',
            label: 'Consumer Number',
            width: 120
        },
        {
            key: 'institution_name',
            label: 'Institution Name',
            width: 200
        },
        {
            key: 'institution_level',
            label: 'Level',
            width: 120,
            render: (value) => value ? (
                <Badge size="sm" variant="light" color="blue">
                    {value}
                </Badge>
            ) : '-'
        },
        {
            key: 'region_name',
            label: 'Region',
            width: 150
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
                    <Title order={2} mb={4}>Institutions</Title>
                    <Text c="dimmed" size="sm">
                        Manage institution consumers in the system
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
                    emptyMessage="No institutions found"
                    enableExport={true}
                />

                {/* Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={() => {
                        close();
                        setEditingInstitution(null);
                        reset();
                    }}
                    title="Edit Institution"
                    centered
                    size="lg"
                >
                    {editingInstitution && (
                        <form onSubmit={handleSubmit}>
                            <Stack gap="md">
                                <Group grow>
                                    <Box>
                                        <Text size="xs" c="dimmed" fw={500}>ID Number</Text>
                                        <Text size="sm">{editingInstitution.identification_number}</Text>
                                    </Box>
                                    <Box>
                                        <Text size="xs" c="dimmed" fw={500}>Consumer ID</Text>
                                        <Text size="sm">{editingInstitution.consumer_id}</Text>
                                    </Box>
                                </Group>

                                <ThemedInput
                                    label="Institution Name"
                                    value={data.institution_name}
                                    onChange={(e) => setData('institution_name', e.target.value)}
                                    error={errors.institution_name}
                                    required
                                />

                                <ThemedInput
                                    label="Level"
                                    value={data.institution_level}
                                    onChange={(e) => setData('institution_level', e.target.value)}
                                    error={errors.institution_level}
                                />

                                <ThemedInput
                                    label="Region"
                                    value={data.region_name}
                                    onChange={(e) => setData('region_name', e.target.value)}
                                    error={errors.region_name}
                                />

                                <Group justify="flex-end" mt="md">
                                    <ThemedButton
                                        themeVariant="subtle"
                                        onClick={() => {
                                            close();
                                            setEditingInstitution(null);
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
                            Are you sure you want to delete institution "{deletingInstitution?.institution_name}"?
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
