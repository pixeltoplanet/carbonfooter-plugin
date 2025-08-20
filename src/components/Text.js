import { __experimentalText as WordPressText } from "@wordpress/components";

const Text = ({ children }) => {
	return (
		<WordPressText isBlock size="16px">
			{children}
		</WordPressText>
	);
};
export default Text;
