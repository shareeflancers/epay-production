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
    Box,
    Badge,
    Alert
} from '@mantine/core';
import { AdminLayout } from '@/Components/Layout';
import { useTheme } from '@/theme';
import { adminNavItems } from '@/config/navigation';
import { router } from '@inertiajs/react';
import axios from 'axios';

export default function TestChallans() {
    const { colorConfig } = useTheme();
    const [loading, setLoading] = useState(false);
    const [institutionIds, setInstitutionIds] = useState('');
    const [regionIds, setRegionIds] = useState('');
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);

    const handleGenerate = async () => {
        setLoading(true);
        setResult(null);
        setError(null);

        try {
            const res = await axios.post('/admin/test-challans/generate', {
                institution_ids: institutionIds.split(',').map(id => id.trim()).filter(Boolean),
                region_ids: regionIds.split(',').map(id => id.trim()).filter(Boolean),
            });

            setResult(res.data);
        } catch (err) {
            console.error('Generation Error:', err);
            if (err.response) {
                // If it's a 422 Validation Error
                if (err.response.status === 422 && err.response.data.errors) {
                    const validationErrors = Object.values(err.response.data.errors).flat().join(' ');
                    setError(`Validation Error: ${validationErrors}`);
                } 
                // If it's a 419 CSRF Token Error
                else if (err.response.status === 419) {
                    setError('Session expired. Please refresh the page and try again.');
                }
                // Fallback for other server errors
                else {
                    setError(
                        err.response.data?.message || 
                        (typeof err.response.data === 'string' ? `Server Error (${err.response.status})` : JSON.stringify(err.response.data)) || 
                        'An error occurred during generation.'
                    );
                }
            } else {
                setError(err.message || 'An error occurred during generation.');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <AdminLayout title="Test Challans Generator" navItems={adminNavItems}>
            <Container fluid py="xl">
                <Stack gap="xl">
                    <Box>
                        <Group gap="sm" align="center">
                            <Title order={2}>Test Challans Generator</Title>
                            <Badge
                                variant="light"
                                size="sm"
                                style={{
                                    backgroundColor: colorConfig.light,
                                    color: colorConfig.primary,
                                }}
                            >
                                Testing
                            </Badge>
                        </Group>
                        <Text c="dimmed" size="sm" mt={4}>
                            Generate up to 3 test active challans using random data for specific institutions and regions.
                        </Text>
                    </Box>

                    <Paper p="xl" radius="md" withBorder>
                        <Stack gap="md">
                            <Title order={4}>Generation Parameters</Title>
                            
                            <TextInput
                                label="Institution IDs (Comma Separated)"
                                placeholder="e.g. 1, 2, 3"
                                value={institutionIds}
                                onChange={(e) => setInstitutionIds(e.target.value)}
                                description="Array of institution IDs to generate challans for."
                            />
                            
                            <TextInput
                                label="Region IDs (Comma Separated)"
                                placeholder="e.g. 10, 20"
                                value={regionIds}
                                onChange={(e) => setRegionIds(e.target.value)}
                                description="Optional. Array of region IDs. If provided, will generate for all institutions in these regions."
                            />

                            <Button 
                                onClick={handleGenerate} 
                                loading={loading} 
                                color={colorConfig.primary}
                                disabled={!institutionIds && !regionIds}
                            >
                                Generate Test Challans
                            </Button>
                        </Stack>
                    </Paper>

                    {result && (
                        <Alert title="Success" color="green" variant="light">
                            <Text>{result.message}</Text>
                        </Alert>
                    )}

                    {error && (
                        <Alert title="Error" color="red" variant="light">
                            <Text>{error}</Text>
                        </Alert>
                    )}
                </Stack>
            </Container>
        </AdminLayout>
    );
}
