import React, { useState } from 'react';
import {
    Title,
    Text,
    Card,
    Group,
    Stack,
    Box,
    TextInput,
    Badge,
    Select,
    Button,
    Anchor,
} from '@mantine/core';
import { router } from '@inertiajs/react';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { useTheme } from '../../../theme';
import { adminNavItems } from '../../../config/navigation';

export default function AnalyticalReport({ institutions, filterOptions, filters, summaryTotals }) {
    const { ui, colorConfig } = useTheme();
    const [search, setSearch] = useState('');

    // Filter state
    const [institutionId, setInstitutionId] = useState(filters.institution_id || null);
    const [classId, setClassId] = useState(filters.school_class_id || null);
    const [section, setSection] = useState(filters.section || '');

    const handleFilter = () => {
        router.get('/admin/reports/analytical', {
            institution_id: institutionId,
            school_class_id: classId,
            section: section
        }, {
            preserveState: true,
            replace: true
        });
    };

    const handleReset = () => {
        setInstitutionId(null);
        setClassId(null);
        setSection('');
        router.get('/admin/reports/analytical');
    };

    const handlePageChange = (page) => {
        router.get('/admin/reports/analytical', {
            ...filters,
            page: page
        }, {
            preserveState: true,
            replace: true
        });
    };

    const columns = [
        {
            key: 'name',
            label: 'Institution / School',
            render: (val, row) => (
                <Anchor 
                    fw={500} 
                    size="sm"
                    underline="hover"
                    onClick={(e) => {
                        e.preventDefault();
                        router.get(`/admin/reports/analytical/institution/${row.id}`);
                    }}
                    href={`/admin/reports/analytical/institution/${row.id}`}
                >
                    {val}
                </Anchor>
            )
        },
        {
            key: 'total_count',
            label: 'Total Challans',
            render: (val) => (
                <Badge variant="light" color="blue" size="lg">
                    {val}
                </Badge>
            )
        },
        {
            key: 'paid_count',
            label: 'Paid',
            render: (val) => (
                <Badge variant="light" color="green" size="lg">
                    {val}
                </Badge>
            )
        },
        {
            key: 'unpaid_count',
            label: 'Unpaid',
            render: (val) => (
                <Badge variant="light" color="orange" size="lg">
                    {val}
                </Badge>
            )
        },
        {
            key: 'synced_count',
            label: 'Synced to SMS',
            render: (val) => (
                <Badge variant="light" color="teal" size="lg">
                    {val}
                </Badge>
            )
        },
        {
            key: 'collection_rate',
            label: 'Collection Rate',
            render: (val, row) => (
                <Text size="sm" fw={700}>
                    {row.total_count > 0
                        ? `${Math.round((row.paid_count / row.total_count) * 100)}%`
                        : '0%'}
                </Text>
            )
        }
    ];

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Analytical Reports</Title>
                    <Text c="dimmed" size="sm">
                        Build customized reports by selecting school, class, and section filters.
                    </Text>
                </Box>

                {/* Filter Area */}
                <Card shadow="sm" radius="md" padding="lg" style={{ background: ui.cardBg, border: `1px solid ${ui.border}` }}>
                    <Stack gap="md">
                        <Group align="flex-end">
                            <Select
                                label="School / Institution"
                                placeholder="Select School"
                                data={filterOptions.institutions.map(i => ({ value: i.id.toString(), label: i.label }))}
                                value={institutionId}
                                onChange={setInstitutionId}
                                clearable
                                searchable
                                style={{ flex: 1 }}
                            />
                            <Select
                                label="Class"
                                placeholder="Select Class"
                                data={filterOptions.classes.map(c => ({ value: c.id.toString(), label: c.label }))}
                                value={classId}
                                onChange={setClassId}
                                clearable
                                searchable
                                style={{ flex: 1 }}
                            />
                            <TextInput
                                label="Section"
                                placeholder="e.g. A, B, Blue"
                                value={section}
                                onChange={(e) => setSection(e.currentTarget.value)}
                                style={{ flex: 1 }}
                            />
                            <Button onClick={handleFilter} color={colorConfig.primary}>Generate Report</Button>
                            <Button variant="subtle" color="gray" onClick={handleReset}>Reset</Button>
                        </Group>
                    </Stack>
                </Card>

                {/* Statistics Cards */}
                <Group grow>
                    <Card shadow="xs" padding="md" radius="md" withBorder>
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Total (All Filtered)</Text>
                        <Text fw={700} size="xl">{summaryTotals.total.toLocaleString()}</Text>
                    </Card>
                    <Card shadow="xs" padding="md" radius="md" withBorder>
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Paid (All Filtered)</Text>
                        <Text fw={700} size="xl" c="green">{summaryTotals.paid.toLocaleString()}</Text>
                    </Card>
                    <Card shadow="xs" padding="md" radius="md" withBorder>
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Synced (All Filtered)</Text>
                        <Text fw={700} size="xl" c="teal">{summaryTotals.synced.toLocaleString()}</Text>
                    </Card>
                </Group>

                {/* Report Table using DataTable */}
                <DataTable
                    columns={columns}
                    data={institutions.data}
                    pagination={institutions}
                    onPageChange={handlePageChange}
                    showActions={false}
                    emptyMessage="No data found for the selected filters."
                />
            </Stack>
        </AdminLayout>
    );
}
