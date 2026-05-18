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
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [selectedBackup, setSelectedBackup] = useState(null);

    const form = useForm({
        import_file: null,
        import_username: '',
        import_password: '',
    });

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

    const openImportModal = (existingBackup = null) => {
        setSelectedBackup(existingBackup);
        form.reset();
        setImportModalOpen(true);
    };

    const handleImportSubmit = (e) => {
        e.preventDefault();

        let formData = new FormData();
        formData.append('import_username', form.data.import_username);
        formData.append('import_password', form.data.import_password);

        if (selectedBackup) {
            // Need a file object, but we are importing existing.
            // But we didn't add "import existing" to controller directly without file.
            // Oh, the controller expects a file upload.
            // To support existing backup we need to either download/re-upload it or send filename.
            alert('Restoring from an existing backup requires the file to be uploaded for security. Please download it first, then use "Upload & Import".');
            setImportModalOpen(false);
            return;
        } else {
            if (!form.data.import_file) {
                form.setError('import_file', 'Please select a file to import');
                return;
            }
        }

        form.post('/admin/settings/database-backups/import', {
            preserveScroll: true,
            onSuccess: () => setImportModalOpen(false),
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Box p="md">
                <Group justify="space-between" mb="lg">
                    <Title order={2}>Database Backups</Title>
                    <Group>
                        <Button
                            leftSection={<IconUpload size={16} />}
                            variant="light"
                            color="orange"
                            onClick={() => openImportModal()}
                        >
                            Upload & Import
                        </Button>
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

                <Modal
                    opened={importModalOpen}
                    onClose={() => !form.processing && setImportModalOpen(false)}
                    title={<Text fw={600} c="red">Database Import (DANGER)</Text>}
                    centered
                >
                    <Box pos="relative">
                        <LoadingOverlay visible={form.processing} zIndex={1000} overlayProps={{ radius: "sm", blur: 2 }} />

                        <Alert color="red" mb="md" title="Warning!">
                            Importing a database will OVERWRITE all existing data. This action cannot be undone. Ensure you know what you are doing.
                        </Alert>

                        <form onSubmit={handleImportSubmit}>
                            <Stack gap="md">
                                {!selectedBackup && (
                                    <FileInput
                                        required
                                        label="Database File (.sql)"
                                        placeholder="Select .sql backup file"
                                        accept=".sql,.txt"
                                        onChange={(file) => form.setData('import_file', file)}
                                        error={form.errors.import_file}
                                    />
                                )}

                                <TextInput
                                    required
                                    label="Import Username"
                                    placeholder="Enter authorized username"
                                    value={form.data.import_username}
                                    onChange={(e) => form.setData('import_username', e.target.value)}
                                    error={form.errors.import_username}
                                />

                                <PasswordInput
                                    required
                                    label="Import Password"
                                    placeholder="Enter import password"
                                    value={form.data.import_password}
                                    onChange={(e) => form.setData('import_password', e.target.value)}
                                    error={form.errors.import_password}
                                />

                                <Button type="submit" color="red" mt="md" fullWidth>
                                    Confirm & Import Database
                                </Button>
                            </Stack>
                        </form>
                    </Box>
                </Modal>
            </Box>
        </AdminLayout>
    );
}
