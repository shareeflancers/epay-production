import { useState, useRef } from 'react';
import {
    Table,
    TextInput,
    Group,
    Button,
    ActionIcon,
    Text,
    Card,
    Pagination,
    Select,
    Badge,
    Tooltip,
    Box,
    Menu,
    Loader,
    Center,
} from '@mantine/core';
import {
    IconDownload,
    IconFileSpreadsheet,
    IconFileTypePdf,
    IconPrinter,
    IconChevronUp,
    IconChevronDown,
    IconSelector
} from '@tabler/icons-react';
import { Link } from '@inertiajs/react';
import { useTheme } from '../../theme';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { IconGripVertical } from '@tabler/icons-react';

// Icons
const SearchIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
);

const PlusIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M5 12h14"/><path d="M12 5v14"/>
    </svg>
);

const EditIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
    </svg>
);

const DeleteIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
    </svg>
);

const ViewIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
    </svg>
);

const MoreIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
    </svg>
);

/**
 * Reusable DataTable Component
 *
 * @param {Object} props
 * @param {Array} props.columns - Column definitions [{ key, label, sortable?, render?, width? }]
 * @param {Array} props.data - Row data from Laravel
 * @param {string} props.title - Table title
 * @param {boolean} props.showAddButton - Show add button
 * @param {string} props.addButtonLabel - Label for add button
 * @param {string} props.addButtonHref - Link for add button (Inertia route)
 * @param {Function} props.onAdd - Callback when add button clicked (instead of href)
 * @param {Function} props.onEdit - Callback when edit action clicked, receives row
 * @param {Function} props.onDelete - Callback when delete action clicked, receives row
 * @param {Function} props.onView - Callback when view action clicked, receives row
 * @param {boolean} props.showActions - Show action column (edit/delete)
 * @param {boolean} props.showSearch - Show search input
 * @param {boolean} props.showPagination - Show pagination
 * @param {Object} props.pagination - Laravel pagination object { current_page, last_page, per_page, total }
 * @param {Function} props.onPageChange - Callback when page changes
 * @param {Function} props.onSearch - Callback when search changes
 * @param {boolean} props.loading - Show loading state
 * @param {string} props.emptyMessage - Message when no data
 */
// Sortable Row Component
function SortableRow({ children, id, ...props }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        cursor: isDragging ? 'grabbing' : 'default',
        zIndex: isDragging ? 1 : 0,
        position: 'relative',
    };

    return (
        <Table.Tr
            ref={setNodeRef}
            style={style}
            {...props}
        >
            {children(attributes, listeners)}
        </Table.Tr>
    );
}

/**
 * Reusable DataTable Component
 * ...
 * @param {Function} props.onReorder - Callback for reordering (receives new ordered items)
 */
export default function DataTable({
    columns = [],
    data = [],
    title = '',
    showAddButton = false,
    addButtonLabel = 'Add New',
    addButtonHref = null,
    onAdd = null,
    onEdit = null,
    onDelete = null,
    onView = null,
    showActions = true,
    showSearch = true,
    showPagination = true,
    pagination = null,
    onPageChange = null,
    onSearch = null,
    loading = false,
    emptyMessage = 'No records found',
    perPageOptions = ['10', '25', '50', '100'],
    onPerPageChange = null,
    enableExport = true,
    onReorder = null,
}) {
    const { ui, gradient } = useTheme();
    const [searchValue, setSearchValue] = useState('');
    const tableRef = useRef(null);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const handleDragEnd = (event) => {
        const { active, over } = event;

        if (active.id !== over.id) {
            const oldIndex = data.findIndex((item) => (item.id || item) === active.id);
            const newIndex = data.findIndex((item) => (item.id || item) === over.id);

            const newItems = arrayMove(data, oldIndex, newIndex);

            if (onReorder) {
                onReorder(newItems);
            }
        }
    };

    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchValue(value);
        if (onSearch) {
            onSearch(value);
        }
    };

    const handleAddClick = () => {
        if (onAdd) {
            onAdd();
        }
    };

    const handleExport = (format) => {
        const timestamp = new Date().toISOString().split('T')[0];
        const fileName = `${title || 'export'}_${timestamp}`;

        // 1. Excel Export
        if (format === 'excel') {
            // Map data to columns (excluding actions)
            const exportData = data.map(row => {
                const newRow = {};
                columns.forEach(col => {
                    if (col.key === 'is_active') {
                        newRow[col.label] = row[col.key] ? 'Active' : 'Inactive';
                    } else {
                        newRow[col.label] = row[col.key];
                    }
                });
                return newRow;
            });
            const ws = XLSX.utils.json_to_sheet(exportData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
            XLSX.writeFile(wb, `${fileName}.xlsx`);
        }

        // 2. PDF Export
        else if (format === 'pdf') {
            const doc = new jsPDF();

            // Extract headers and data
            const headers = columns.map(col => col.label);
            const body = data.map(row => columns.map(col => {
                if (col.key === 'is_active') {
                    return row[col.key] ? 'Active' : 'Inactive';
                }
                return row[col.key];
            }));

            doc.text(title || 'Export', 14, 15);
            autoTable(doc, {
                head: [headers],
                body: body,
                startY: 20,
            });
            doc.save(`${fileName}.pdf`);
        }

        // 3. Print via Iframe
        else if (format === 'print') {
             // Create an iframe
             const iframe = document.createElement('iframe');
             iframe.style.position = 'absolute';
             iframe.style.width = '0px';
             iframe.style.height = '0px';
             iframe.style.border = 'none';
             document.body.appendChild(iframe);

             const iframeDoc = iframe.contentWindow.document;

             // Construct the HTML content
             // We need to copy styles. For simplicity, we'll use basic table styles here
             // or try to copy app stylesheets.
             let styles = `
                <style>
                    body { font-family: system-ui, -apple-system, sans-serif; padding: 20px; }
                    h1 { margin-bottom: 20px; font-size: 24px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
                    .badge-green { background-color: #e6fcf5; color: #087f5b; }
                    .badge-red { background-color: #fff5f5; color: #c92a2a; }
                </style>
             `;

             // Generate Table HTML
             let tableHtml = `<h1>${title || 'Export'}</h1>`;
             tableHtml += '<table><thead><tr>';
             columns.forEach(col => {
                 tableHtml += `<th>${col.label}</th>`;
             });
             tableHtml += '</tr></thead><tbody>';

             data.forEach(row => {
                 tableHtml += '<tr>';
                 columns.forEach(col => {
                     let cellContent = row[col.key];
                     if (col.key === 'is_active') {
                         const isActive = !!row[col.key];
                         cellContent = `<span class="badge ${isActive ? 'badge-green' : 'badge-red'}">${isActive ? 'Active' : 'Inactive'}</span>`;
                     } else if (col.render) {
                         // Basic render string check needed?
                         // If render returns React component, we can't easily stringify it.
                         // We'll fall back to raw value for print if render is complex.
                         // But for now, use raw value or simple mapping.
                         cellContent = cellContent ?? '-';
                     } else {
                         cellContent = cellContent ?? '-';
                     }
                     tableHtml += `<td>${cellContent}</td>`;
                 });
                 tableHtml += '</tr>';
             });
             tableHtml += '</tbody></table>';

             iframeDoc.open();
             iframeDoc.write('<html><head>' + styles + '</head><body>' + tableHtml + '</body></html>');
             iframeDoc.close();

             iframe.contentWindow.focus();
             setTimeout(() => {
                 iframe.contentWindow.print();
                 document.body.removeChild(iframe);
             }, 500);
        }
    };

    const renderCellValue = (row, column) => {
        if (column.render) {
            return column.render(row[column.key], row);
        }

        const value = row[column.key];

        // Handle boolean values
        if (typeof value === 'boolean' || column.key === 'is_active') {
            return (
                <Badge
                    variant="light"
                    color={value || value === 1 ? 'green' : 'red'}
                    size="sm"
                >
                    {value || value === 1 ? 'Active' : 'Inactive'}
                </Badge>
            );
        }

        return value ?? '-';
    };

    const AddButton = () => {
        if (!showAddButton) return null;

        const buttonProps = {
            leftSection: <PlusIcon />,
            variant: 'gradient',
            gradient: { from: gradient.from, to: gradient.to },
            size: 'sm',
            radius: 'md',
        };

        if (addButtonHref) {
            return (
                <Button component={Link} href={addButtonHref} {...buttonProps}>
                    {addButtonLabel}
                </Button>
            );
        }

        return (
            <Button onClick={handleAddClick} {...buttonProps}>
                {addButtonLabel}
            </Button>
        );
    };

    const content = (
        <Card
            shadow="sm"
            radius="md"
            padding="lg"
            style={{
                background: ui.cardBg,
                border: `1px solid ${ui.border}`,
            }}
        >
            {/* Header */}
            <Group justify="space-between" mb="md">
                <Group>
                    {title && <Text fw={600} size="lg">{title}</Text>}
                </Group>
                <Group>
                    {showSearch && (
                        <TextInput
                            placeholder="Search..."
                            leftSection={<SearchIcon />}
                            value={searchValue}
                            onChange={handleSearchChange}
                            size="sm"
                            radius="md"
                            style={{ width: 250 }}
                        />
                    )}
                    {enableExport && (
                        <Menu shadow="md" width={200}>
                            <Menu.Target>
                                <Button
                                    leftSection={<IconDownload size={16} />}
                                    variant="light"
                                    color="gray"
                                >
                                    Export
                                </Button>
                            </Menu.Target>

                            <Menu.Dropdown>
                                <Menu.Label>Download</Menu.Label>
                                <Menu.Item
                                    leftSection={<IconFileSpreadsheet size={14} />}
                                    onClick={() => handleExport('excel')}
                                >
                                    Excel
                                </Menu.Item>
                                <Menu.Item
                                    leftSection={<IconFileTypePdf size={14} />}
                                    onClick={() => handleExport('pdf')}
                                >
                                    PDF
                                </Menu.Item>
                                <Menu.Divider />
                                <Menu.Label>Action</Menu.Label>
                                <Menu.Item
                                    leftSection={<IconPrinter size={14} />}
                                    onClick={() => handleExport('print')}
                                >
                                    Print
                                </Menu.Item>
                            </Menu.Dropdown>
                        </Menu>
                    )}
                    <AddButton />
                </Group>
            </Group>

            {/* Table */}
            <Table.ScrollContainer minWidth={600}>
                <Table ref={tableRef} verticalSpacing="sm" highlightOnHover striped>
                    <Table.Thead>
                        <Table.Tr>
                            {onReorder && <Table.Th style={{ width: 40 }} />}
                            {columns.map((column) => (
                                <Table.Th
                                    key={column.key}
                                    style={{ width: column.width }}
                                >
                                    {column.label}
                                </Table.Th>
                            ))}
                            {showActions && (onEdit || onDelete || onView) && (
                                <Table.Th style={{ width: 100 }}>Actions</Table.Th>
                            )}
                        </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {loading ? (
                            <Table.Tr>
                                <Table.Td colSpan={columns.length + (showActions ? 1 : 0) + (onReorder ? 1 : 0)}>
                                    <Center py="xl">
                                        <Loader size="md" />
                                    </Center>
                                </Table.Td>
                            </Table.Tr>
                        ) : data.length === 0 ? (
                            <Table.Tr>
                                <Table.Td colSpan={columns.length + (showActions ? 1 : 0) + (onReorder ? 1 : 0)}>
                                    <Text ta="center" c="dimmed" py="xl">
                                        {emptyMessage}
                                    </Text>
                                </Table.Td>
                            </Table.Tr>
                        ) : (
                        onReorder ? (
                                <SortableContext
                                    items={data.map(d => d.id)}
                                    strategy={verticalListSortingStrategy}
                                >
                                    {data.map((row) => (
                                        <SortableRow key={row.id} id={row.id}>
                                            {(attributes, listeners) => (
                                                <>
                                                    <Table.Td style={{ width: 40 }}>
                                                        <Center {...attributes} {...listeners} style={{ cursor: 'grab' }}>
                                                            <IconGripVertical size={16} stroke={1.5} color="gray" />
                                                        </Center>
                                                    </Table.Td>
                                                    {columns.map((column) => (
                                                        <Table.Td key={column.key}>
                                                            {renderCellValue(row, column)}
                                                        </Table.Td>
                                                    ))}
                                                    {showActions && (onEdit || onDelete || onView) && (
                                                        <Table.Td>
                                                            <Group gap={4}>
                                                                {onView && (
                                                                    <Tooltip label="View">
                                                                        <ActionIcon variant="subtle" color="blue" size="sm" onClick={() => onView(row)}>
                                                                            <ViewIcon />
                                                                        </ActionIcon>
                                                                    </Tooltip>
                                                                )}
                                                                {onEdit && (
                                                                    <Tooltip label="Edit">
                                                                        <ActionIcon variant="subtle" color="yellow" size="sm" onClick={() => onEdit(row)}>
                                                                            <EditIcon />
                                                                        </ActionIcon>
                                                                    </Tooltip>
                                                                )}
                                                                {onDelete && (
                                                                    <Tooltip label="Delete">
                                                                        <ActionIcon variant="subtle" color="red" size="sm" onClick={() => onDelete(row)}>
                                                                            <DeleteIcon />
                                                                        </ActionIcon>
                                                                    </Tooltip>
                                                                )}
                                                            </Group>
                                                        </Table.Td>
                                                    )}
                                                </>
                                            )}
                                        </SortableRow>
                                    ))}
                                </SortableContext>
                            ) : (
                                data.map((row, index) => (
                                    <Table.Tr key={row.id || index}>
                                        {columns.map((column) => (
                                            <Table.Td key={column.key}>
                                                {renderCellValue(row, column)}
                                            </Table.Td>
                                        ))}
                                        {showActions && (onEdit || onDelete || onView) && (
                                            <Table.Td>
                                                <Group gap={4}>
                                                    {onView && (
                                                        <Tooltip label="View">
                                                            <ActionIcon variant="subtle" color="blue" size="sm" onClick={() => onView(row)}>
                                                                <ViewIcon />
                                                            </ActionIcon>
                                                        </Tooltip>
                                                    )}
                                                    {onEdit && (
                                                        <Tooltip label="Edit">
                                                            <ActionIcon variant="subtle" color="yellow" size="sm" onClick={() => onEdit(row)}>
                                                                <EditIcon />
                                                            </ActionIcon>
                                                        </Tooltip>
                                                    )}
                                                    {onDelete && (
                                                        <Tooltip label="Delete">
                                                            <ActionIcon variant="subtle" color="red" size="sm" onClick={() => onDelete(row)}>
                                                                <DeleteIcon />
                                                            </ActionIcon>
                                                        </Tooltip>
                                                    )}
                                                </Group>
                                            </Table.Td>
                                        )}
                                    </Table.Tr>
                                ))
                            )
                        )}
                    </Table.Tbody>
                </Table>
            </Table.ScrollContainer>

            {/* Pagination */}
            {showPagination && pagination && pagination.last_page > 1 && (
                <Group justify="space-between" mt="md">
                    <Group gap="xs">
                        <Text size="sm" c="dimmed">
                            Showing {((pagination.current_page - 1) * pagination.per_page) + 1} to{' '}
                            {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of{' '}
                            {pagination.total} entries
                        </Text>
                        {onPerPageChange && (
                            <Select
                                size="xs"
                                value={String(pagination.per_page)}
                                onChange={onPerPageChange}
                                data={perPageOptions}
                                style={{ width: 80 }}
                            />
                        )}
                    </Group>
                    <Pagination
                        value={pagination.current_page}
                        total={pagination.last_page}
                        onChange={onPageChange}
                        size="sm"
                        radius="md"
                    />
                </Group>
            )}
        </Card>
    );

    if (onReorder) {
        return (
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
            >
                {content}
            </DndContext>
        );
    }

    return content;
}
