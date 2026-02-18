import { useState, useMemo, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Badge,
} from '@mantine/core';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { adminNavItems } from '../../../config/navigation';

export default function StudentIndex({ consumers, filters }) {
    const [tableData, setTableData] = useState(consumers.data);

    // Sync table data with props
    useEffect(() => {
        setTableData(consumers.data);
    }, [consumers.data]);

    // Handle search
    const handleSearch = (value) => {
        router.get('/admin/consumers/student', {
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
        router.get('/admin/consumers/student', {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Handle per page change
    const handlePerPageChange = (perPage) => {
        router.get('/admin/consumers/student', {
            ...filters,
            per_page: perPage,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Column definitions for students
    const columns = useMemo(() => [
        {
            key: 'consumer_number',
            label: 'Consumer Number',
            width: 120
        },
        {
            key: 'identification_number',
            label: 'B-Form Number',
            width: 120
        },
        {
            key: 'name',
            label: 'Student Name',
            width: 180
        },
        {
            key: 'father_or_guardian_name',
            label: 'Father/Guardian',
            width: 180
        },
        {
            key: 'institution_name',
            label: 'Institution',
            width: 200
        },
        {
            key: 'institution_level',
            label: 'Level',
            width: 100,
            render: (value) => value ? (
                <Badge size="sm" variant="light" color="blue">
                    {value}
                </Badge>
            ) : '-'
        },
        {
            key: 'class',
            label: 'Class',
            width: 80
        },
        {
            key: 'section',
            label: 'Section',
            width: 80
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
            render: (value) => value ? (
                <Badge size="sm" variant="light" color="green">
                    Active
                </Badge>
            ) : (
                <Badge size="sm" variant="light" color="red">
                    Inactive
                </Badge>
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
                {/* Page Header */}
                <Box>
                    <Title order={2} mb={4}>Students</Title>
                    <Text c="dimmed" size="sm">
                        Manage student consumers in the system
                    </Text>
                </Box>

                {/* DataTable */}
                <DataTable
                    title=""
                    columns={columns}
                    data={tableData}
                    showAddButton={false}
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
                    emptyMessage="No students found"
                    enableExport={true}
                />
            </Stack>
        </AdminLayout>
    );
}
