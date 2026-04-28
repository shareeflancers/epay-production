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
    ThemedModal,
    ThemedSelect
} from '../../../Components/UI';

export default function YearSessionIndex({ sessions, classes, institutions, filters }) {
    const [opened, { open, close }] = useDisclosure(false);
    const [deleteOpened, { open: openDelete, close: closeDelete }] = useDisclosure(false);
    const [editingSession, setEditingSession] = useState(null);
    const [deletingSession, setDeletingSession] = useState(null);
    const [tableData, setTableData] = useState(sessions.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(sessions.data);
    }, [sessions.data]);

    // Prepare select options
    const classOptions = useMemo(
        () => classes.map(c => ({ value: String(c.id), label: c.name })),
        [classes]
    );
    const institutionOptions = useMemo(
        () => institutions.map(i => ({ value: String(i.id), label: i.name })),
        [institutions]
    );

    const emptyForm = {
        name: '',
        start_date: '',
        end_date: '',
        school_class_id: '',
        institution_id: '',
        is_active: true,
    };

    // Form for create/edit
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);

    // Handle add button click
    const handleAdd = () => {
        setEditingSession(null);
        reset();
        setData(emptyForm);
        open();
    };

    // Handle edit button click
    const handleEdit = (row) => {
        setEditingSession(row);
        setData({
            name: row.name || '',
            start_date: row.start_date ? row.start_date.substring(0, 10) : '',
            end_date:   row.end_date   ? row.end_date.substring(0, 10)   : '',
            school_class_id: String(row.school_class_id || ''),
            institution_id:  String(row.institution_id  || ''),
            is_active: !!row.is_active,
        });
        open();
    };

    // Handle delete button click
    const handleDelete = (row) => {
        setDeletingSession(row);
        openDelete();
    };

    // Confirm delete
    const confirmDelete = () => {
        router.delete(`/admin/year-sessions/${deletingSession.id}`, {
            onSuccess: () => {
                closeDelete();
                setDeletingSession(null);
            },
        });
    };

    // Handle status toggle
    const handleStatusToggle = (row, checked) => {
        router.put(`/admin/year-sessions/${row.id}/status`, {
            is_active: checked,
        }, {
            preserveScroll: true,
        });
    };

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/year-sessions', { ...filters, search: value, page: 1 }, {
            preserveState: true, preserveScroll: true,
        });
    };

    // Handle page change
    const handlePageChange = (page) => {
        router.get('/admin/year-sessions', { ...filters, page }, {
            preserveState: true, preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/year-sessions', { ...filters, per_page: perPage, page: 1 }, {
            preserveState: true, preserveScroll: true,
        });
    };

    // Handle sort
    const handleSort = (column) => {
        const direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/year-sessions', { ...filters, sort: column, direction, page: 1 }, {
            preserveState: true, preserveScroll: true,
        });
    };

    // Submit form
    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingSession) {
            put(`/admin/year-sessions/${editingSession.id}`, {
                onSuccess: () => { close(); reset(); },
            });
        } else {
            post('/admin/year-sessions', {
                onSuccess: () => { close(); reset(); },
            });
        }
    };

    const fmt = (val) => val ? new Date(val).toLocaleDateString() : '-';

    // Column definitions
    const columns = useMemo(() => [
        { key: 'name', label: 'Session Name', sortable: true },
        {
            key: 'school_class',
            label: 'Class',
            render: (_, row) => row.school_class?.name || '-',
        },
        {
            key: 'institution',
            label: 'Institution',
            render: (_, row) => row.institution?.name || '-',
        },
        {
            key: 'start_date',
            label: 'Start Date',
            width: 120,
            sortable: true,
            render: (value) => fmt(value),
        },
        {
            key: 'end_date',
            label: 'End Date',
            width: 120,
            sortable: true,
            render: (value) => fmt(value),
        },
        {
            key: 'is_active',
            label: 'Status',
            width: 90,
            render: (value, row) => (
                <div onClick={(e) => e.stopPropagation()}>
                    <ThemedSwitch
                        checked={!!value}
                        onChange={(e) => handleStatusToggle(row, e.currentTarget.checked)}
                        size="sm"
                    />
                </div>
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            width: 110,
            render: (value) => fmt(value),
        },
    ], []);

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Year Sessions</Title>
                    <Text c="dimmed" size="sm">
                        Manage academic year sessions per class and institution
                    </Text>
                </Box>

                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={true}
                    addButtonLabel="Add Session"
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    showSearch={true}
                    onSearch={handleSearch}
                    showPagination={true}
                    pagination={{
                        current_page: sessions.current_page,
                        last_page:    sessions.last_page,
                        per_page:     sessions.per_page,
                        total:        sessions.total,
                    }}
                    onPageChange={handlePageChange}
                    onPerPageChange={handlePerPageChange}
                    emptyMessage="No year sessions found"
                    enableExport={true}
                    onSort={handleSort}
                    sortColumn={filters.sort}
                    sortDirection={filters.direction}
                />

                {/* Create / Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={close}
                    title={editingSession ? 'Edit Year Session' : 'Add New Year Session'}
                    centered
                >
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <ThemedInput
                                label="Session Name"
                                placeholder="e.g. 2024-2025"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />

                            {/* Start & End dates side by side */}
                            <Group grow>
                                <ThemedInput
                                    label="Start Date"
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    error={errors.start_date}
                                />
                                <ThemedInput
                                    label="End Date"
                                    type="date"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    error={errors.end_date}
                                    min={data.start_date || undefined}
                                />
                            </Group>

                            <ThemedSelect
                                label="Class"
                                placeholder="Select class"
                                data={classOptions}
                                value={data.school_class_id}
                                onChange={(val) => setData('school_class_id', val)}
                                error={errors.school_class_id}
                                required
                                searchable
                                styles={{ input: { cursor: 'pointer' } }}
                            />

                            <ThemedSelect
                                label="Institution"
                                placeholder="Select institution"
                                data={institutionOptions}
                                value={data.institution_id}
                                onChange={(val) => setData('institution_id', val)}
                                error={errors.institution_id}
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
                                <ThemedButton themeVariant="subtle" onClick={close}>
                                    Cancel
                                </ThemedButton>
                                <ThemedButton type="submit" loading={processing} themeVariant="primary">
                                    {editingSession ? 'Update' : 'Create'}
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
                            Are you sure you want to delete "{deletingSession?.name}"?
                        </Text>
                        <Text size="xs" c="dimmed">
                            Sessions with associated challans cannot be deleted.
                        </Text>
                        <Group justify="flex-end" mt="md">
                            <ThemedButton themeVariant="subtle" onClick={closeDelete}>
                                Cancel
                            </ThemedButton>
                            <ThemedButton themeVariant="danger" onClick={confirmDelete}>
                                Delete
                            </ThemedButton>
                        </Group>
                    </Stack>
                </ThemedModal>
            </Stack>
        </AdminLayout>
    );
}
