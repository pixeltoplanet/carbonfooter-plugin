import { __experimentalHeading as WordPressHeading } from "@wordpress/components";

const PageTitle = ({ title }) => {
	return (
		<WordPressHeading
			level={1}
			weight="bold"
			style={{ fontWeight: "bold", fontSize: "2.5rem" }}
		>
			{title}
		</WordPressHeading>
	);
};

const PageHeading = ({ title }) => {
	return (
		<WordPressHeading
			level={2}
			weight="bold"
			style={{ fontWeight: "bold", fontSize: "2rem" }}
		>
			{title}
		</WordPressHeading>
	);
};

export { PageTitle, PageHeading };
