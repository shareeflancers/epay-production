import React from 'react';
import {
    Title,
    Text,
    Group,
    Badge,
    Stack,
    ActionIcon,
    Breadcrumbs,
    Anchor,
    Box,
} from '@mantine/core';
import { router, Head } from '@inertiajs/react';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { useTheme } from '../../../theme';
import { adminNavItems } from '../../../config/navigation';

const ReceiptIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>
    </svg>
);

export default function InstitutionShow({ institution, stats }) {
    const { ui } = useTheme();

    const breadcrumbs = [
        { title: 'Reports', href: '/admin/reports/analytical' },
        { title: institution.name, href: '#' },
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
        {
            key: 'class_name',
            label: 'Class',
            render: (val) => val || 'N/A'
        },
        {
            key: 'section',
            label: 'Section',
            render: (val) => val || 'N/A'
        },
        {
            key: 'total_count',
            label: 'Total Challans',
            render: (val) => val
        },
        {
            key: 'paid_count',
            label: 'Paid',
            render: (val) => val
        },
        {
            key: 'unpaid_count',
            label: 'Unpaid',
            render: (val) => val
        },
        {
            key: 'synced_count',
            label: 'Synced to SMS',
            render: (val) => val
        },
        {
            key: 'actions',
            label: 'Action',
            render: (val, row) => (
                <ActionIcon
                    variant="light"
                    color="blue"
                    title="View Students"
                    onClick={() => handleClassClick(row)}
                >
                    <ReceiptIcon />
                </ActionIcon>
            )
        }
    ];

    const handleClassClick = (stat) => {
        router.get('/admin/reports/analytical/students', {
            institution_id: institution.id,
            school_class_id: stat.school_class_id,
            section: stat.section
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Head title={`${institution.name} - Reports`} />
            <Stack gap="lg">
                <Breadcrumbs>{breadcrumbs}</Breadcrumbs>

                <Box>
                    <Title order={2}>{institution.name}</Title>
                    <Text c="dimmed" size="sm">Class and Section wise distribution</Text>
                </Box>

                <DataTable
                    columns={columns}
                    data={stats}
                    showActions={false}
                    showPagination={false}
                    emptyMessage="No class records found for this institution."
                />
            </Stack>
        </AdminLayout>
    );
}
