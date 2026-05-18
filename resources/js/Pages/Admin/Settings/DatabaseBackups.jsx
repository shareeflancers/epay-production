import { useState } from 'react';
import {
    Box,
    Title,
    Text,
    Group,
    Button,
    Table,
    ActionIcon,
    Card,
    Badge,
    Modal,
    PasswordInput,
    TextInput,
    FileInput,
    Stack,
    Alert,
    LoadingOverlay
} from '@mantine/core';
import { useForm, router } from '@inertiajs/react';
import { IconDatabase, IconDownload, IconTrash, IconUpload, IconInfoCircle } from '@tabler/icons-react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import { adminNavItems } from '@/config/navigation';
import { useTheme } from '@/theme';

export default function DatabaseBackups({ backups }) {
    const { ui } = useTheme();
    const [isGenerating, setIsGenerating] = useState(false);

    const handleGenerateBackup = () => {
        setIsGenerating(true);
        router.post('/admin/settings/database-backups', {}, {
            onFinish: () => setIsGenerating(false),
            preserveScroll: true,
        });
    };

    const handleDelete = (filename) => {
        if (confirm(`Are you sure you want to delete ${filename}?`)) {
            router.delete(`/admin/settings/database-backups/${filename}`, {
                preserveScroll: true,
            });
        }
    };

    const handleDownload = (filename) => {
        window.location.href = `/admin/settings/database-backups/${filename}/download`;
    };



    return (
        <AdminLayout navItems={adminNavItems}>
            <Box p="md">
                <Group justify="space-between" mb="lg">
                    <Title order={2}>Database Backups</Title>
                    <Group>
                        <Button
                            leftSection={<IconDatabase size={16} />}
                            onClick={handleGenerateBackup}
                            loading={isGenerating}
                        >
                            Generate New Backup
                        </Button>
                    </Group>
                </Group>

                <Alert icon={<IconInfoCircle size={16} />} title="Automated Backups" color="blue" mb="lg">
                    The system automatically generates a database backup every hour. Backups older than 7 days are automatically removed to save disk space.
                </Alert>

                <Card withBorder radius="md" p="md" bg={ui.cardBg}>
                    <Table verticalSpacing="sm">
                        <Table.Thead>
                            <Table.Tr>
                                <Table.Th>File Name</Table.Th>
                                <Table.Th>Date Created</Table.Th>
                                <Table.Th>Size</Table.Th>
                                <Table.Th style={{ width: 150 }}>Actions</Table.Th>
                            </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {backups.length > 0 ? (
                                backups.map((backup) => (
                                    <Table.Tr key={backup.name}>
                                        <Table.Td>
                                            <Text size="sm" fw={500}>{backup.name}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge color="gray" variant="light">{backup.date}</Badge>
                                        </Table.Td>
                                        <Table.Td>{backup.size}</Table.Td>
                                        <Table.Td>
                                            <Group gap="xs">
                                                <ActionIcon
                                                    variant="light"
                                                    color="blue"
                                                    onClick={() => handleDownload(backup.name)}
                                                    title="Download Backup"
                                                >
                                                    <IconDownload size={16} />
                                                </ActionIcon>
                                                <ActionIcon
                                                    variant="light"
                                                    color="red"
                                                    onClick={() => handleDelete(backup.name)}
                                                    title="Delete Backup"
                                                >
                                                    <IconTrash size={16} />
                                                </ActionIcon>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))
                            ) : (
                                <Table.Tr>
                                    <Table.Td colSpan={4}>
                                        <Text c="dimmed" ta="center" py="xl">No database backups found.</Text>
                                    </Table.Td>
                                </Table.Tr>
                            )}
                        </Table.Tbody>
                    </Table>
                </Card>

            </Box>
        </AdminLayout>
    );
}
