import React, { useState, useEffect } from 'react';
import {
    Container,
    Divider,
    Paper,
    Title,
    Text,
    Stack,
    Group,
    Table,
    Badge,
    Tabs,
    ScrollArea,
    Pagination,
    ActionIcon,
    Tooltip,
    Modal,
    Code,
    Box,
    JsonInput
} from '@mantine/core';
import {
    IconShieldLock,
    IconEye,
    IconSearch,
    IconTerminal2,
    IconUserShield,
    IconHistory,
    IconWorld
} from '@tabler/icons-react';
import { AdminLayout } from '@/Components/Layout';
import { useTheme } from '@/theme';
import { adminNavItems } from '@/config/navigation';
import axios from 'axios';
import { format } from 'date-fns';

export default function SecurityAudit({ auditLogs: initialAuditLogs, apiLogs: initialApiLogs }) {
    const { primaryColor } = useTheme();
    const [auditData, setAuditData] = useState(initialAuditLogs);
    const [apiData, setApiData] = useState(initialApiLogs);
    const [loading, setLoading] = useState(false);
    const [selectedLog, setSelectedLog] = useState(null);
    const [modalOpened, setModalOpened] = useState(false);

    const fetchAuditLogs = async (page = 1) => {
        setLoading(true);
        try {
            const res = await axios.get(`/admin/security-audit/audit-logs?page=${page}`);
            setAuditData(res.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const fetchApiLogs = async (page = 1) => {
        setLoading(true);
        try {
            const res = await axios.get(`/admin/security-audit/api-logs?page=${page}`);
            setApiData(res.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const viewDetails = (log) => {
        setSelectedLog(log);
        setModalOpened(true);
    };

    return (
        <AdminLayout title="Security Audit" navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Security & Audit Oversight</Title>
                    <Text c="dimmed" size="sm">
                       Monitor all administrative actions and external system API calls for accountability and security auditing.
                    </Text>
                </Box>

                <Tabs defaultValue="audit" variant="outline" radius="md">
                    <Tabs.List>
                        <Tabs.Tab value="audit" leftSection={<IconUserShield size={16} />}>Admin Actions</Tabs.Tab>
                        <Tabs.Tab value="api" leftSection={<IconTerminal2 size={16} />}>External API Logs</Tabs.Tab>
                    </Tabs.List>

                    <Tabs.Panel value="audit" pt="xl">
                        <Paper withBorder radius="md">
                            <ScrollArea h={600}>
                                <Table verticalSpacing="sm" highlightOnHover>
                                    <Table.Thead style={{ position: 'sticky', top: 0, backgroundColor: '#fff', zIndex: 1 }}>
                                        <Table.Tr>
                                            <Table.Th>User</Table.Th>
                                            <Table.Th>Action</Table.Th>
                                            <Table.Th>IP Address</Table.Th>
                                            <Table.Th>Date & Time</Table.Th>
                                            <Table.Th>Details</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {auditData.data.map((log) => (
                                            <Table.Tr key={log.id}>
                                                <Table.Td>
                                                    <Stack gap={0}>
                                                        <Text size="sm" fw={500}>{log.user?.name || 'Public Visitor'}</Text>
                                                        <Text size="xs" c="dimmed">{log.user?.email || 'N/A'}</Text>
                                                    </Stack>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Code color="blue.1" c="blue.9">{log.action}</Code>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Code size="xs">{log.ip_address}</Code>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="xs">{format(new Date(log.created_at), 'dd MMM yyyy HH:mm:ss')}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <ActionIcon variant="light" color="blue" onClick={() => viewDetails(log)}>
                                                        <IconEye size={16} />
                                                    </ActionIcon>
                                                </Table.Td>
                                            </Table.Tr>
                                        ))}
                                    </Table.Tbody>
                                </Table>
                            </ScrollArea>
                            <Divider />
                            <Group justify="center" py="md">
                                <Pagination
                                    total={auditData.last_page}
                                    value={auditData.current_page}
                                    onChange={(p) => fetchAuditLogs(p)}
                                    color={primaryColor.primary}
                                />
                            </Group>
                        </Paper>
                    </Tabs.Panel>

                    <Tabs.Panel value="api" pt="xl">
                        <Paper withBorder radius="md">
                            <ScrollArea h={600}>
                                <Table verticalSpacing="sm" highlightOnHover>
                                    <Table.Thead style={{ position: 'sticky', top: 0, backgroundColor: '#fff', zIndex: 1 }}>
                                        <Table.Tr>
                                            <Table.Th>Endpoint</Table.Th>
                                            <Table.Th>Method</Table.Th>
                                            <Table.Th>IP Address</Table.Th>
                                            <Table.Th>Status</Table.Th>
                                            <Table.Th>Time</Table.Th>
                                            <Table.Th>Timestamp</Table.Th>
                                            <Table.Th>View</Table.Th>
                                        </Table.Tr>
                                    </Table.Thead>
                                    <Table.Tbody>
                                        {apiData.data.map((log) => (
                                            <Table.Tr key={log.id}>
                                                <Table.Td style={{ maxWidth: 200 }}>
                                                    <Text size="xs" truncate title={log.endpoint}>{log.endpoint}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Badge size="xs" variant="outline" color={log.method === 'GET' ? 'blue' : 'green'}>
                                                        {log.method}
                                                    </Badge>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Code size="xs">{log.ip_address}</Code>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Badge size="xs" color={log.status_code >= 400 ? 'red' : 'green'}>
                                                        {log.status_code}
                                                    </Badge>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="xs" fw={500} c={log.duration_ms > 1000 ? 'red' : 'inherit'}>
                                                        {log.duration_ms}ms
                                                    </Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <Text size="xs">{format(new Date(log.created_at), 'dd MMM yyyy HH:mm:ss')}</Text>
                                                </Table.Td>
                                                <Table.Td>
                                                    <ActionIcon variant="light" color="cyan" onClick={() => viewDetails(log)}>
                                                        <IconEye size={16} />
                                                    </ActionIcon>
                                                </Table.Td>
                                            </Table.Tr>
                                        ))}
                                    </Table.Tbody>
                                </Table>
                            </ScrollArea>
                            <Divider />
                            <Group justify="center" py="md">
                                <Pagination
                                    total={apiData.last_page}
                                    value={apiData.current_page}
                                    onChange={(p) => fetchApiLogs(p)}
                                    color={primaryColor.primary}
                                />
                            </Group>
                        </Paper>
                    </Tabs.Panel>

                </Tabs>
            </Stack>

            <Modal
                opened={modalOpened}
                onClose={() => setModalOpened(false)}
                title={<Title order={4}>Log Details</Title>}
                size="xl"
                radius="md"
            >
                {selectedLog && (
                    <Stack gap="md">
                        <Group grow>
                            <Box>
                                <Text size="xs" fw={700} c="dimmed">TIMESTAMP</Text>
                                <Text size="sm">{format(new Date(selectedLog.created_at), 'PPPP p')}</Text>
                            </Box>
                            <Box>
                                <Text size="xs" fw={700} c="dimmed">IP ADDRESS</Text>
                                <Text size="sm">{selectedLog.ip_address}</Text>
                            </Box>
                        </Group>

                        {selectedLog.action && (
                            <Box>
                                <Text size="xs" fw={700} c="dimmed">ACTION / PATH</Text>
                                <Code block>{selectedLog.action}</Code>
                            </Box>
                        )}

                        {selectedLog.endpoint && (
                            <Box>
                                <Text size="xs" fw={700} c="dimmed">FULL ENDPOINT</Text>
                                <Code block>{selectedLog.endpoint}</Code>
                            </Box>
                        )}

                        <Box>
                            <Text size="xs" fw={700} c="dimmed" mb="xs">DATA PAYLOAD</Text>
                            <JsonInput
                                value={JSON.stringify(
                                    selectedLog.description
                                        ? JSON.parse(selectedLog.description)
                                        : { request: selectedLog.request_payload, response: selectedLog.response_payload },
                                    null, 2
                                )}
                                readOnly
                                autosize
                                minRows={5}
                                maxRows={20}
                                size="xs"
                            />
                        </Box>

                        {selectedLog.user_agent && (
                            <Box>
                                <Text size="xs" fw={700} c="dimmed">USER AGENT</Text>
                                <Text size="xs" style={{ wordBreak: 'break-all' }}>{selectedLog.user_agent}</Text>
                            </Box>
                        )}
                    </Stack>
                )}
            </Modal>
        </AdminLayout>
    );
}
