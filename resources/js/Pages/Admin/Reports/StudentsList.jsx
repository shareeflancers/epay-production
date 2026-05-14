import React from 'react';
import {
    Title,
    Text,
    Group,
    Badge,
    Stack,
    Breadcrumbs,
    Anchor,
    Box,
    ActionIcon,
} from '@mantine/core';
import { router, Head } from '@inertiajs/react';
import { IconEye } from '@tabler/icons-react';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { adminNavItems } from '../../../config/navigation';
import { useTheme } from '../../../theme';

const statusMap = {
    U: { label: 'Unpaid', color: 'orange' },
    P: { label: 'Paid', color: 'green' },
    B: { label: 'Blocked', color: 'red' },
};

export default function StudentsList({ challans, filters, institution, schoolClass }) {
    const { ui } = useTheme();

    const breadcrumbs = [
        { title: 'Reports', href: '/admin/reports/analytical' },
        { title: institution.name, href: `/admin/reports/analytical/institution/${institution.id}` },
        { title: `${schoolClass?.name || 'Class'} - ${filters.section || 'All Sections'}`, href: '#' },
    ].map((item, index) => (
        <Anchor href={item.href} key={index} onClick={(e) => {
            if (item.href !== '#') {
                e.preventDefault();
                router.get(item.href);
            }
        }}>
            {item.title}
        </Anchor>
    ));

    const columns = [
        { key: 'challan_no', label: 'Challan No' },
        {
            key: 'consumer',
            label: 'Student Details',
            render: (val, row) => {
                const profile = row.consumer?.profile_details?.[0];
                return (
                    <Box>
                        <Text size="sm" fw={500}>{profile?.name || 'N/A'}</Text>
                        <Text size="xs" c="dimmed">F: {profile?.father_or_guardian_name || 'N/A'}</Text>
                        <Text size="xs" c="dimmed">ID: {row.consumer?.consumer_number || '-'}</Text>
                    </Box>
                );
            }
        },
        {
            key: 'amount_within_dueDate',
            label: 'Amount',
            render: (val) => `₨ ${parseFloat(val || 0).toLocaleString()}`
        },
        {
            key: 'status',
            label: 'Status',
            render: (val) => {
                const info = statusMap[val] || { label: val, color: 'gray' };
                return <Badge color={info.color} size="sm">{info.label}</Badge>;
            }
        },
        {
            key: 'sms_sync',
            label: 'SMS Sync',
            render: (val, row) => {
                if (row.status !== 'P') return <Text size="sm" c="dimmed">-</Text>;
                return val ? (
                    <Badge color="green" size="sm" variant="light">Synced</Badge>
                ) : (
                    <Badge color="red" size="sm" variant="light">Failed</Badge>
                );
            }
        },
        {
            key: 'due_date',
            label: 'Due Date',
            render: (val) => val ? new Date(val).toLocaleDateString() : '-'
        }
    ];

    const handlePageChange = (page) => {
        router.get('/admin/reports/analytical/students', { ...filters, page }, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Head title="Student List - Report" />
            
            <Stack gap="lg">
                <Breadcrumbs>{breadcrumbs}</Breadcrumbs>

                <Box>
                    <Title order={2}>{schoolClass?.name || 'Class'} Students</Title>
                    <Text c="dimmed" size="sm">
                        Showing students for {institution.name} - Section {filters.section || 'All'}
                    </Text>
                </Box>

                <DataTable
                    columns={columns}
                    data={challans.data}
                    pagination={challans}
                    onPageChange={handlePageChange}
                    showActions={false}
                />
            </Stack>
        </AdminLayout>
    );
}
