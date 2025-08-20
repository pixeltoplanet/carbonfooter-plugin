import React from "react";

// Main Table Component
export const Table = ({ data, columns, className = "", style = {} }) => {
	return (
		<table
			className={className}
			style={{
				width: "100%",
				borderCollapse: "collapse",
				fontSize: "14px",
				...style,
			}}
		>
			<TableHeader columns={columns} />
			<tbody>
				{data.map((item, index) => (
					<TableRow
						key={item.id || index}
						data={item}
						columns={columns}
						style={{ borderBottom: "1px solid #eee" }}
					/>
				))}
			</tbody>
		</table>
	);
};

// Table Header Component
export const TableHeader = ({ columns, className = "", style = {} }) => {
	return (
		<thead>
			<tr
				className={className}
				style={{
					borderBottom: "1px solid #ddd",
					// backgroundColor: "#f9f9f9",
					...style,
				}}
			>
				{columns.map((column) => (
					<th
						key={column.key}
						style={{
							padding: "12px 0",
							textAlign: column.align || "left",
							fontSize: "14px",
							width: column.width,
						}}
					>
						{column.label}
					</th>
				))}
			</tr>
		</thead>
	);
};

// Table Row Component
export const TableRow = ({ data, columns, className = "", style = {} }) => {
	return (
		<tr className={className} style={style}>
			{columns.map((column) => (
				<TableCell
					key={column.key}
					value={data[column.key]}
					align={column.align}
					style={{ padding: "12px 0" }}
				/>
			))}
		</tr>
	);
};

// Table Cell Component
export const TableCell = ({
	value,
	align = "left",
	className = "",
	style = {},
}) => {
	return (
		<td
			className={className}
			style={{
				textAlign: align,
				...style,
			}}
		>
			{value}
		</td>
	);
};

// Action Button Component for tables
export const ActionButton = ({
	href,
	label,
	target,
	rel,
	className = "",
	style = {},
}) => {
	return (
		<a
			href={href}
			target={target}
			rel={rel}
			className={className}
			style={{
				color: "#2271b1",
				textDecoration: "none",
				fontSize: "12px",
				padding: "4px 8px",
				border: "1px solid #2271b1",
				borderRadius: "3px",
				...style,
			}}
		>
			{label}
		</a>
	);
};

// Action Buttons Container
export const ActionButtons = ({ actions, className = "", style = {} }) => {
	return (
		<div
			className={className}
			style={{
				display: "flex",
				gap: "8px",
				justifyContent: "flex-end",
				...style,
			}}
		>
			{actions.map((action, index) => (
				<ActionButton key={`action-${action.label}`} {...action} />
			))}
		</div>
	);
};

export default Table;
