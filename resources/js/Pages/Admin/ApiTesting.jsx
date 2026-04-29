import React, { useState } from 'react';
import {
    Container,
    Paper,
    Title,
    Text,
    TextInput,
    Button,
    Stack,
    Group,
    Code,
    Divider,
    Alert,
    ActionIcon,
    Table,
    Badge,
    Tabs,
    Box,
    JsonInput
} from '@mantine/core';
import { AdminLayout } from '@/Components/Layout';
import { useTheme } from '@/theme';
import { adminNavItems } from '@/config/navigation';
import axios from 'axios';

export default function ApiTesting() {
    const { primaryColor } = useTheme();
    const [loading, setLoading] = useState(false);
    const [response, setResponse] = useState(null);
    const [error, setError] = useState(null);

    // Auth Headers
    const [auth, setAuth] = useState({
        username: '1-Link',
        password: 'DaX11QEwEvcpIKXVnf'
    });

    // Inputs
    const [singleId, setSingleId] = useState('');
    const [bulkIds, setBulkIds] = useState('');

    const callApi = async (method, endpoint, params = null, body = null) => {
        setLoading(true);
        setResponse(null);
        setError(null);

        try {
            const config = {
                method,
                url: `/api${endpoint}`,
                headers: {
                    'username': auth.username,
                    'password': auth.password,
                    'Accept': 'application/json'
                }
            };

            if (params) config.params = params;
            if (body) config.data = body;

            const res = await axios(config);
            setResponse(res.data);
        } catch (err) {
            setError(err.response?.data || { message: err.message });
            setResponse(err.response?.data || null);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AdminLayout title="API Testing Sandbox" navItems={adminNavItems}>
            <Container fluid py="xl">
                <Stack gap="xl">
                    {/* Page Header */}
                    <Box>
                        <Group gap="sm" align="center">
                            <Title order={2}>API Testing</Title>
                            <Badge
                                variant="light"
                                size="sm"
                                style={{
                                    backgroundColor: primaryColor.light,
                                    color: primaryColor.primary,
                                }}
                            >
                                Developer
                            </Badge>
                        </Group>
                        <Text c="dimmed" size="sm" mt={4}>
                            Test the Challan Printing and Categories API endpoints directly.
                        </Text>
                    </Box>

                    <Paper p="xl" radius="md" withBorder>
                        <Stack gap="md">
                            <Title order={3}>Authentication Headers</Title>
                            <Text size="sm" c="dimmed">These credentials are required for all external system API calls.</Text>
                            <Group grow>
                                <TextInput
                                    label="Username Header"
                                    value={auth.username}
                                    onChange={(e) => setAuth({ ...auth, username: e.target.value })}
                                />
                                <TextInput
                                    label="Password Header"
                                    type="password"
                                    value={auth.password}
                                    onChange={(e) => setAuth({ ...auth, password: e.target.value })}
                                />
                            </Group>
                        </Stack>
                    </Paper>

                    <Tabs defaultValue="categories" variant="outline" radius="md">
                        <Tabs.List>
                            <Tabs.Tab value="categories">1. Fee Categories</Tabs.Tab>
                            <Tabs.Tab value="single">2. Single Challan URL</Tabs.Tab>
                            <Tabs.Tab value="bulk">3. Bulk Print URL</Tabs.Tab>
                        </Tabs.List>

                        <Tabs.Panel value="categories" pt="xl">
                            <Paper p="xl" withBorder>
                                <Stack align="flex-start">
                                    <Title order={4}>Fetch Fee Categories</Title>
                                    <Text size="sm">Retrieves all active fee fund categories for synchronization.</Text>
                                    <Code>GET /api/fee-categories</Code>
                                    <Button
                                        onClick={() => callApi('get', '/fee-categories')}
                                        loading={loading}
                                        color={primaryColor}
                                    >
                                        Execute API Call
                                    </Button>
                                </Stack>
                            </Paper>
                        </Tabs.Panel>

                        <Tabs.Panel value="single" pt="xl">
                            <Paper p="xl" withBorder>
                                <Stack align="flex-start">
                                    <Title order={4}>Get Single Challan Print URL</Title>
                                    <Text size="sm">Fetch the printable link for a specific student identification number.</Text>
                                    <Code>GET /api/challan/single?identification_number=...</Code>
                                    <TextInput
                                        label="Identification Number (CNIC / B-Form / Consumer No)"
                                        placeholder="e.g. 66167364138"
                                        value={singleId}
                                        onChange={(e) => setSingleId(e.target.value)}
                                        style={{ width: '100%', maxWidth: 400 }}
                                    />
                                    <Button
                                        onClick={() => callApi('get', '/challan/single', { identification_number: singleId })}
                                        loading={loading}
                                        color={primaryColor}
                                        disabled={!singleId}
                                    >
                                        Execute API Call
                                    </Button>
                                </Stack>
                            </Paper>
                        </Tabs.Panel>

                        <Tabs.Panel value="bulk" pt="xl">
                            <Paper p="xl" withBorder>
                                <Stack align="flex-start">
                                    <Title order={4}>Get Bulk Print URL</Title>
                                    <Text size="sm">Fetch a unified printable link for multiple identification numbers.</Text>
                                    <Code>POST /api/challan/bulk</Code>
                                    <TextInput
                                        label="Identification Numbers (Comma Separated)"
                                        placeholder="e.g. 66167364138, 66167364139"
                                        value={bulkIds}
                                        onChange={(e) => setBulkIds(e.target.value)}
                                        style={{ width: '100%', maxWidth: 400 }}
                                    />
                                    <Button
                                        onClick={() => callApi('post', '/challan/bulk', null, { identification_numbers: bulkIds.split(',').map(i => i.trim()).filter(i => i) })}
                                        loading={loading}
                                        color={primaryColor}
                                        disabled={!bulkIds}
                                    >
                                        Execute API Call
                                    </Button>
                                </Stack>
                            </Paper>
                        </Tabs.Panel>
                    </Tabs>

                    {(response || error) && (
                        <Paper p="xl" radius="md" withBorder style={{ backgroundColor: '#f8f9fa' }}>
                            <Stack gap="md">
                                <Group justify="space-between">
                                    <Title order={4}>API Response</Title>
                                    <Badge color={error ? 'red' : 'green'}>{error ? 'Failed' : 'Success'}</Badge>
                                </Group>

                                {response?.data?.print_url && (
                                    <Alert title="Print Link Generated" color="green" variant="light">
                                        <Text mb="sm">The print URL has been successfully generated:</Text>
                                        <Button component="a" href={response.data.print_url} target="_blank" size="xs">Open Challan Form</Button>
                                    </Alert>
                                )}

                                {response?.data?.bulk_print_url && (
                                    <Alert title="Bulk Print Link Generated" color="green" variant="light">
                                        <Text mb="sm">The bulk print URL has been successfully generated:</Text>
                                        <Button component="a" href={response.data.bulk_print_url} target="_blank" size="xs">Open Bulk Print View</Button>
                                    </Alert>
                                )}

                                <JsonInput
                                    label="JSON Raw Output"
                                    value={JSON.stringify(response || error, null, 2)}
                                    readOnly
                                    autosize
                                    minRows={4}
                                    maxRows={20}
                                />
                            </Stack>
                        </Paper>
                    )}
                </Stack>
            </Container>
        </AdminLayout>
    );
}
