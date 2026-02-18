import React, { useState } from 'react';
import {
    Title, Text, TextInput, Button, Group, Box, Grid, Card, Stack,
    NumberInput, Badge, Code, Divider, Textarea,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { AdminLayout } from '../../Components/Layout';
import { useTheme } from '../../theme';
import { adminNavItems } from '../../config/navigation';

// Icons
const SearchIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
);

const CreditCardIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>
    </svg>
);

const CheckIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M20 6 9 17l-5-5"/>
    </svg>
);

const AlertIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
    </svg>
);

// Parse 1Link formatted amount string like "+0000000100000" → 1000.00
const parseOneLinkAmount = (formatted) => {
    if (!formatted || typeof formatted !== 'string') return 0;
    const cleaned = formatted.replace(/[^0-9]/g, '');
    return parseInt(cleaned, 10) / 100;
};

// Generate a random 6-char alphanumeric ID
const randomTranAuthId = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let id = '';
    for (let i = 0; i < 6; i++) id += chars.charAt(Math.floor(Math.random() * chars.length));
    return id;
};

// Bank mnemonics for random selection
const BANK_MNEMONICS = ['HBL', 'UBL', 'MCB', 'ABL', 'NBP', 'BAFL', 'JSBL', 'MEZN'];

export default function OneLinkTesting() {
    const { ui, colorConfig } = useTheme();
    const [inquiryResponse, setInquiryResponse] = useState(null);
    const [inquiryData, setInquiryData] = useState(null); // raw parsed JSON
    const [paymentResponse, setPaymentResponse] = useState(null);
    const [inquiryStatus, setInquiryStatus] = useState(null);
    const [paymentStatus, setPaymentStatus] = useState(null);
    const [loadingInquiry, setLoadingInquiry] = useState(false);
    const [loadingPayment, setLoadingPayment] = useState(false);

    const inquiryForm = useForm({
        initialValues: {
            consumer_number: '',
            username: '',
            password: '',
        },
        validate: {
            consumer_number: (v) => (v ? null : 'Required'),
            username: (v) => (v ? null : 'Required'),
            password: (v) => (v ? null : 'Required'),
        },
    });

    const paymentForm = useForm({
        initialValues: {
            consumer_number: '',
            username: '',
            password: '',
            tran_auth_id: 'TX1234',
            transaction_amount: 100,
            tran_date: new Date().toISOString().slice(0, 10).replace(/-/g, ''),
            tran_time: new Date().toTimeString().slice(0, 8).replace(/:/g, ''),
            bank_mnemonic: 'MOCK',
            reserved: '',
        },
        validate: {
            consumer_number: (v) => (v ? null : 'Required'),
            tran_auth_id: (v) => (v ? null : 'Required'),
            transaction_amount: (v) => (v > 0 ? null : 'Must be > 0'),
            username: (v) => (v ? null : 'Required'),
            password: (v) => (v ? null : 'Required'),
        },
    });

    const handleInquiry = async (values) => {
        setLoadingInquiry(true);
        setInquiryResponse(null);
        setInquiryData(null);
        setInquiryStatus(null);
        try {
            const res = await fetch('/api/bill-inquiry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    username: values.username,
                    password: values.password,
                },
                body: JSON.stringify({ consumer_number: values.consumer_number }),
            });
            const data = await res.json();
            setInquiryResponse(JSON.stringify(data, null, 2));
            setInquiryStatus(res.ok ? 'success' : 'error');

            // On success, auto-fill payment form
            if (res.ok && data.response_Code === '00') {
                setInquiryData(data);
                const amount = parseOneLinkAmount(data.amount_within_dueDate);
                const now = new Date();
                paymentForm.setValues({
                    consumer_number: values.consumer_number,
                    username: values.username,
                    password: values.password,
                    transaction_amount: amount,
                    tran_auth_id: data.tran_auth_Id || randomTranAuthId(),
                    tran_date: data.due_date || now.toISOString().slice(0, 10).replace(/-/g, ''),
                    tran_time: now.toTimeString().slice(0, 8).replace(/:/g, ''),
                    bank_mnemonic: BANK_MNEMONICS[Math.floor(Math.random() * BANK_MNEMONICS.length)],
                    reserved: `Payment for ${data.consumer_Detail || 'consumer'}`,
                });
            }
        } catch (err) {
            setInquiryResponse(JSON.stringify({ error: err.message }, null, 2));
            setInquiryStatus('error');
        } finally {
            setLoadingInquiry(false);
        }
    };

    const handlePayment = async (values) => {
        setLoadingPayment(true);
        setPaymentResponse(null);
        setPaymentStatus(null);
        try {
            const res = await fetch('/api/bill-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    username: values.username,
                    password: values.password,
                },
                body: JSON.stringify({
                    consumer_number: values.consumer_number,
                    tran_auth_id: values.tran_auth_id,
                    transaction_amount: values.transaction_amount,
                    tran_date: values.tran_date,
                    tran_time: values.tran_time,
                    bank_mnemonic: values.bank_mnemonic,
                    reserved: values.reserved,
                }),
            });
            const data = await res.json();
            setPaymentResponse(JSON.stringify(data, null, 2));
            setPaymentStatus(res.ok ? 'success' : 'error');
        } catch (err) {
            setPaymentResponse(JSON.stringify({ error: err.message }, null, 2));
            setPaymentStatus('error');
        } finally {
            setLoadingPayment(false);
        }
    };

    const ResponseBlock = ({ response, status }) => {
        if (!response) return null;
        return (
            <Box mt="md">
                <Group gap="xs" mb="xs">
                    {status === 'success' ? (
                        <Badge
                            variant="light"
                            size="sm"
                            leftSection={<CheckIcon />}
                            style={{ backgroundColor: '#d3f9d8', color: '#2f9e44' }}
                        >
                            Success
                        </Badge>
                    ) : (
                        <Badge
                            variant="light"
                            size="sm"
                            leftSection={<AlertIcon />}
                            style={{ backgroundColor: '#ffe3e3', color: '#e03131' }}
                        >
                            Error
                        </Badge>
                    )}
                </Group>
                <Code
                    block
                    style={{
                        background: ui.cardBg,
                        border: `1px solid ${ui.border}`,
                        borderRadius: 8,
                        padding: 16,
                        fontSize: '0.85rem',
                        whiteSpace: 'pre-wrap',
                        maxHeight: 300,
                        overflow: 'auto',
                    }}
                >
                    {response}
                </Code>
            </Box>
        );
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                {/* Page Header */}
                <Box>
                    <Group gap="sm" align="center">
                        <Title order={2}>1-Link API Testing</Title>
                        <Badge
                            variant="light"
                            size="sm"
                            style={{
                                backgroundColor: colorConfig.light,
                                color: colorConfig.primary,
                            }}
                        >
                            Developer
                        </Badge>
                    </Group>
                    <Text c="dimmed" size="sm" mt={4}>
                        Test the 1-Link Bill Inquiry and Payment API endpoints directly.
                    </Text>
                </Box>

                <Grid>
                    {/* ── Bill Inquiry Card ── */}
                    <Grid.Col span={{ base: 12, md: 6 }}>
                        <Card
                            shadow="sm"
                            radius="md"
                            padding="lg"
                            style={{
                                background: ui.cardBg,
                                border: `1px solid ${ui.border}`,
                                height: '100%',
                            }}
                        >
                            <Group gap="sm" mb="md">
                                <Box style={{ color: colorConfig.primary }}><SearchIcon /></Box>
                                <Title order={4}>Bill Inquiry</Title>
                            </Group>

                            <form onSubmit={inquiryForm.onSubmit(handleInquiry)}>
                                <Stack gap="sm">
                                    <Group grow>
                                        <TextInput
                                            label="Username"
                                            placeholder="API Username"
                                            size="sm"
                                            {...inquiryForm.getInputProps('username')}
                                        />
                                        <TextInput
                                            type="password"
                                            label="Password"
                                            placeholder="API Password"
                                            size="sm"
                                            {...inquiryForm.getInputProps('password')}
                                        />
                                    </Group>
                                    <TextInput
                                        label="Consumer Number"
                                        placeholder="e.g. TEST001"
                                        size="sm"
                                        {...inquiryForm.getInputProps('consumer_number')}
                                    />
                                    <Button
                                        type="submit"
                                        loading={loadingInquiry}
                                        fullWidth
                                        style={{
                                            background: colorConfig.primary,
                                        }}
                                    >
                                        Send Inquiry
                                    </Button>
                                </Stack>
                            </form>

                            <ResponseBlock response={inquiryResponse} status={inquiryStatus} />

                            {inquiryStatus === 'success' && inquiryData && (
                                <Box
                                    mt="sm"
                                    p="xs"
                                    style={{
                                        background: colorConfig.light,
                                        borderRadius: 8,
                                        border: `1px dashed ${colorConfig.primary}`,
                                        textAlign: 'center',
                                    }}
                                >
                                    <Text size="xs" c="dimmed">
                                        ✓ Payment form auto-filled from inquiry result
                                    </Text>
                                </Box>
                            )}
                        </Card>
                    </Grid.Col>

                    {/* ── Bill Payment Card ── */}
                    <Grid.Col span={{ base: 12, md: 6 }}>
                        <Card
                            shadow="sm"
                            radius="md"
                            padding="lg"
                            style={{
                                background: ui.cardBg,
                                border: `1px solid ${ui.border}`,
                                height: '100%',
                            }}
                        >
                            <Group gap="sm" mb="md">
                                <Box style={{ color: colorConfig.primary }}><CreditCardIcon /></Box>
                                <Title order={4}>Bill Payment</Title>
                            </Group>

                            <form onSubmit={paymentForm.onSubmit(handlePayment)}>
                                <Stack gap="sm">
                                    <Group grow>
                                        <TextInput
                                            label="Username"
                                            placeholder="API Username"
                                            size="sm"
                                            {...paymentForm.getInputProps('username')}
                                        />
                                        <TextInput
                                            type="password"
                                            label="Password"
                                            placeholder="API Password"
                                            size="sm"
                                            {...paymentForm.getInputProps('password')}
                                        />
                                    </Group>

                                    <TextInput
                                        label="Consumer Number"
                                        placeholder="e.g. TEST001"
                                        size="sm"
                                        {...paymentForm.getInputProps('consumer_number')}
                                    />

                                    <Group grow>
                                        <TextInput
                                            label="Tran Auth ID"
                                            placeholder="6 chars max"
                                            maxLength={6}
                                            size="sm"
                                            {...paymentForm.getInputProps('tran_auth_id')}
                                        />
                                        <NumberInput
                                            label="Amount"
                                            min={0}
                                            size="sm"
                                            {...paymentForm.getInputProps('transaction_amount')}
                                        />
                                    </Group>

                                    <Group grow>
                                        <TextInput
                                            label="Tran Date"
                                            placeholder="YYYYMMDD"
                                            size="sm"
                                            {...paymentForm.getInputProps('tran_date')}
                                        />
                                        <TextInput
                                            label="Tran Time"
                                            placeholder="HHMMSS"
                                            size="sm"
                                            {...paymentForm.getInputProps('tran_time')}
                                        />
                                    </Group>

                                    <Group grow>
                                        <TextInput
                                            label="Bank Mnemonic"
                                            size="sm"
                                            {...paymentForm.getInputProps('bank_mnemonic')}
                                        />
                                        <TextInput
                                            label="Reserved"
                                            size="sm"
                                            {...paymentForm.getInputProps('reserved')}
                                        />
                                    </Group>

                                    <Button
                                        type="submit"
                                        loading={loadingPayment}
                                        fullWidth
                                        color="green"
                                    >
                                        Send Payment
                                    </Button>
                                </Stack>
                            </form>

                            <ResponseBlock response={paymentResponse} status={paymentStatus} />
                        </Card>
                    </Grid.Col>
                </Grid>
            </Stack>
        </AdminLayout>
    );
}
