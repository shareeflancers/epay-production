import React, { useState } from 'react';
import {
    Title,
    Text,
    Card,
    Table,
    Group,
    Stack,
    Box,
    TextInput,
    Badge,
    Select,
    Button,
} from '@mantine/core';
import { router } from '@inertiajs/react';
import { AdminLayout } from '../../../Components/Layout';
import { useTheme } from '../../../theme';
import { adminNavItems } from '../../../config/navigation';

export default function AnalyticalReport({ institutions, filterOptions, filters }) {
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

    const filteredInstitutions = institutions.filter(inst =>
        inst.name.toLowerCase().includes(search.toLowerCase())
    );

    const rows = filteredInstitutions.map((inst) => (
        <Table.Tr key={inst.id}>
            <Table.Td>
                <Text fw={500} size="sm">{inst.name}</Text>
            </Table.Td>
            <Table.Td align="center" style={{ textAlign: 'center' }}>
                <Badge variant="light" color="blue" size="lg">
                    {inst.total_count}
                </Badge>
            </Table.Td>
            <Table.Td align="center" style={{ textAlign: 'center' }}>
                <Badge variant="light" color="green" size="lg">
                    {inst.paid_count}
                </Badge>
            </Table.Td>
            <Table.Td align="center" style={{ textAlign: 'center' }}>
                <Badge variant="light" color="orange" size="lg">
                    {inst.unpaid_count}
                </Badge>
            </Table.Td>
            <Table.Td align="center" style={{ textAlign: 'center' }}>
                <Badge variant="light" color="teal" size="lg">
                    {inst.synced_count}
                </Badge>
            </Table.Td>
            <Table.Td align="center" style={{ textAlign: 'center' }}>
                <Text size="sm" fw={700}>
                    {inst.total_count > 0
                        ? `${Math.round((inst.paid_count / inst.total_count) * 100)}%`
                        : '0%'}
                </Text>
            </Table.Td>
        </Table.Tr>
    ));

    // Totals calculation
    const totals = institutions.reduce((acc, inst) => ({
        total: acc.total + inst.total_count,
        paid: acc.paid + inst.paid_count,
        unpaid: acc.unpaid + inst.unpaid_count,
        synced: acc.synced + inst.synced_count,
    }), { total: 0, paid: 0, unpaid: 0, synced: 0 });

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
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Selected Total</Text>
                        <Text fw={700} size="xl">{totals.total.toLocaleString()}</Text>
                    </Card>
                    <Card shadow="xs" padding="md" radius="md" withBorder>
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Selected Paid</Text>
                        <Text fw={700} size="xl" c="green">{totals.paid.toLocaleString()}</Text>
                    </Card>
                    <Card shadow="xs" padding="md" radius="md" withBorder>
                        <Text size="xs" c="dimmed" tt="uppercase" fw={700}>Selected Synced</Text>
                        <Text fw={700} size="xl" c="teal">{totals.synced.toLocaleString()}</Text>
                    </Card>
                </Group>

                {/* Report Table */}
                <Card shadow="sm" radius="md" padding="lg" style={{ background: ui.cardBg, border: `1px solid ${ui.border}` }}>
                    <Stack gap="md">
                        <Group justify="space-between">
                            <Text fw={700}>Report Results</Text>
                            <TextInput
                                placeholder="Filter results by name..."
                                value={search}
                                onChange={(event) => setSearch(event.currentTarget.value)}
                                size="xs"
                                style={{ width: 250 }}
                            />
                        </Group>

                        <Table verticalSpacing="sm" highlightOnHover striped withTableBorder>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Institution / School</Table.Th>
                                    <Table.Th align="center" style={{ textAlign: 'center' }}>Total Challans</Table.Th>
                                    <Table.Th align="center" style={{ textAlign: 'center' }}>Paid</Table.Th>
                                    <Table.Th align="center" style={{ textAlign: 'center' }}>Unpaid</Table.Th>
                                    <Table.Th align="center" style={{ textAlign: 'center' }}>Synced to SMS</Table.Th>
                                    <Table.Th align="center" style={{ textAlign: 'center' }}>Collection Rate</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>{rows}</Table.Tbody>
                            {filteredInstitutions.length > 0 && (
                                <Table.Tfoot>
                                    <Table.Tr>
                                        <Table.Th>Total</Table.Th>
                                        <Table.Th style={{ textAlign: 'center' }}>{totals.total.toLocaleString()}</Table.Th>
                                        <Table.Th style={{ textAlign: 'center' }}>{totals.paid.toLocaleString()}</Table.Th>
                                        <Table.Th style={{ textAlign: 'center' }}>{totals.unpaid.toLocaleString()}</Table.Th>
                                        <Table.Th style={{ textAlign: 'center' }}>{totals.synced.toLocaleString()}</Table.Th>
                                        <Table.Th style={{ textAlign: 'center' }}>
                                            {totals.total > 0 ? `${Math.round((totals.paid / totals.total) * 100)}%` : '0%'}
                                        </Table.Th>
                                    </Table.Tr>
                                </Table.Tfoot>
                            )}
                        </Table>

                        {filteredInstitutions.length === 0 && (
                            <Text align="center" c="dimmed" py="xl">No data found for the selected filters.</Text>
                        )}
                    </Stack>
                </Card>
            </Stack>
        </AdminLayout>
    );
}
