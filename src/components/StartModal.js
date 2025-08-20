import {
	Modal,
	Button,
	ProgressBar,
	__experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useEffect } from "react";
import { __ } from "@wordpress/i18n";

import Text from "./Text";
import { PageHeading } from "./PageTitle";
import { formatEmissions } from "../lib/formatEmissions";

const StartModal = ({
	isOpen,
	closeModal,
	isLoading,
	setIsLoading,
	onMeasurementComplete,
	emissions,
}) => {
	const [progress, setProgress] = useState(0);

	const handleCloseModal = () => {
		closeModal();
		setIsLoading(false);
	};

	useEffect(() => {
		// Only start progress when isLoading is true
		if (isLoading) {
			// Progress bar takes 30 seconds total (30000ms)
			// Update every 300ms, so need 100 steps
			// 30000ms / 100 steps = 300ms per step
			const interval = setInterval(() => {
				setProgress((prevProgress) => {
					const newProgress = prevProgress + 1;
					if (newProgress >= 100) {
						clearInterval(interval);
						setIsLoading(false); // Set isLoading to false when progress reaches 100%
						if (onMeasurementComplete) {
							onMeasurementComplete();
						}
						return 100;
					}
					return newProgress;
				});
			}, 300); // 300ms * 100 steps = 30 seconds total
			return () => clearInterval(interval);
		}
	}, [isLoading, setIsLoading, onMeasurementComplete]);

	// Reset progress when modal opens
	useEffect(() => {
		if (isOpen) {
			setProgress(0);
		}
	}, [isOpen]);

	const title = isLoading ? (
		<>
			{__("Hang on while we are preparing your carbonfooter", "carbonfooter")}
		</>
	) : (
		<>
			{__(
				"The emissions for your home page have been calculated.",
				"carbonfooter",
			)}
		</>
	);

	return (
		<>
			{isOpen && (
				<Modal
					__experimentalHideHeader={true}
					onRequestClose={handleCloseModal}
					size="large"
					isDismissible={false}
					shouldCloseOnClickOutside={false}
					overlayClassName="carbonfooter-modal-overlay"
				>
					<PageHeading title={title} />
					<Button
						onClick={handleCloseModal}
						aria-label={__("Close modal", "carbonfooter")}
						style={{
							position: "absolute",
							top: "10px",
							right: "10px",
							display: isLoading ? "none" : "block",
						}}
						disabled={isLoading}
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							fill="currentColor"
							viewBox="0 0 256 256"
						>
							<title>{__("Close modal", "carbonfooter")}</title>
							<path d="M165.66,101.66,139.31,128l26.35,26.34a8,8,0,0,1-11.32,11.32L128,139.31l-26.34,26.35a8,8,0,0,1-11.32-11.32L116.69,128,90.34,101.66a8,8,0,0,1,11.32-11.32L128,116.69l26.34-26.35a8,8,0,0,1,11.32,11.32ZM232,128A104,104,0,1,1,128,24,104.11,104.11,0,0,1,232,128Zm-16,0a88,88,0,1,0-88,88A88.1,88.1,0,0,0,216,128Z" />
						</svg>
					</Button>

					<Spacer margin={4} />
					{isLoading && (
						<ProgressBar
							className="carbonfooter-progress-bar"
							value={progress}
							label={__(
								"We are measuring your carbon footprint",
								"carbonfooter",
							)}
						/>
					)}
					<Spacer margin={4} />
					{isLoading ? (
						<Text>
							{__(
								"We are measuring the carbon footprint of your home page. This may take 30 seconds.",
								"carbonfooter",
							)}
						</Text>
					) : (
						<Text>
							{emissions && emissions > 0
								? __(
										`Per visit it will emit ${formatEmissions(
											emissions,
										)} of CO2.`,
										"carbonfooter",
									)
								: __(
										"There was an error calculating the emissions for your home page. Please visit the page to trigger re-measurement.",
										"carbonfooter",
									)}
						</Text>
					)}
					<Spacer margin={20} />
					<div className="carbonfooter-modal-buttons">
						<Button
							variant="secondary"
							disabled={isLoading}
							onClick={handleCloseModal}
							style={{
								display: "flex",
								alignItems: "center",
								gap: "0.5rem",
							}}
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="20"
								height="20"
								fill="currentColor"
								viewBox="0 0 256 256"
							>
								<title>Back to settings</title>
								<path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm88-29.84q.06-2.16,0-4.32l14.92-18.64a8,8,0,0,0,1.48-7.06,107.21,107.21,0,0,0-10.88-26.25,8,8,0,0,0-6-3.93l-23.72-2.64q-1.48-1.56-3-3L186,40.54a8,8,0,0,0-3.94-6,107.71,107.71,0,0,0-26.25-10.87,8,8,0,0,0-7.06,1.49L130.16,40Q128,40,125.84,40L107.2,25.11a8,8,0,0,0-7.06-1.48A107.6,107.6,0,0,0,73.89,34.51a8,8,0,0,0-3.93,6L67.32,64.27q-1.56,1.49-3,3L40.54,70a8,8,0,0,0-6,3.94,107.71,107.71,0,0,0-10.87,26.25,8,8,0,0,0,1.49,7.06L40,125.84Q40,128,40,130.16L25.11,148.8a8,8,0,0,0-1.48,7.06,107.21,107.21,0,0,0,10.88,26.25,8,8,0,0,0,6,3.93l23.72,2.64q1.49,1.56,3,3L70,215.46a8,8,0,0,0,3.94,6,107.71,107.71,0,0,0,26.25,10.87,8,8,0,0,0,7.06-1.49L125.84,216q2.16.06,4.32,0l18.64,14.92a8,8,0,0,0,7.06,1.48,107.21,107.21,0,0,0,26.25-10.88,8,8,0,0,0,3.93-6l2.64-23.72q1.56-1.48,3-3L215.46,186a8,8,0,0,0,6-3.94,107.71,107.71,0,0,0,10.87-26.25,8,8,0,0,0-1.49-7.06Zm-16.1-6.5a73.93,73.93,0,0,1,0,8.68,8,8,0,0,0,1.74,5.48l14.19,17.73a91.57,91.57,0,0,1-6.23,15L187,173.11a8,8,0,0,0-5.1,2.64,74.11,74.11,0,0,1-6.14,6.14,8,8,0,0,0-2.64,5.1l-2.51,22.58a91.32,91.32,0,0,1-15,6.23l-17.74-14.19a8,8,0,0,0-5-1.75h-.48a73.93,73.93,0,0,1-8.68,0,8,8,0,0,0-5.48,1.74L100.45,215.8a91.57,91.57,0,0,1-15-6.23L82.89,187a8,8,0,0,0-2.64-5.1,74.11,74.11,0,0,1-6.14-6.14,8,8,0,0,0-5.1-2.64L46.43,170.6a91.32,91.32,0,0,1-6.23-15l14.19-17.74a8,8,0,0,0,1.74-5.48,73.93,73.93,0,0,1,0-8.68,8,8,0,0,0-1.74-5.48L40.2,100.45a91.57,91.57,0,0,1,6.23-15L69,82.89a8,8,0,0,0,5.1-2.64,74.11,74.11,0,0,1,6.14-6.14A8,8,0,0,0,82.89,69L85.4,46.43a91.32,91.32,0,0,1,15-6.23l17.74,14.19a8,8,0,0,0,5.48,1.74,73.93,73.93,0,0,1,8.68,0,8,8,0,0,0,5.48-1.74L155.55,40.2a91.57,91.57,0,0,1,15,6.23L173.11,69a8,8,0,0,0,2.64,5.1,74.11,74.11,0,0,1,6.14,6.14,8,8,0,0,0,5.1,2.64l22.58,2.51a91.32,91.32,0,0,1,6.23,15l-14.19,17.74A8,8,0,0,0,199.87,123.66Z" />
							</svg>

							{__("Back to settings", "carbonfooter")}
						</Button>
						<Button
							variant="primary"
							disabled={isLoading}
							onClick={() => {
								window.open(
									`${window.carbonfooterVars?.siteUrl}#carbonfooter`,
									"_blank",
								);
							}}
							style={{
								display: "flex",
								alignItems: "center",
								gap: "0.5rem",
							}}
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="20"
								height="20"
								fill="currentColor"
								viewBox="0 0 256 256"
							>
								<title>Arrow right</title>
								<path d="M247.31,124.76c-.35-.79-8.82-19.58-27.65-38.41C194.57,61.26,162.88,48,128,48S61.43,61.26,36.34,86.35C17.51,105.18,9,124,8.69,124.76a8,8,0,0,0,0,6.5c.35.79,8.82,19.57,27.65,38.4C61.43,194.74,93.12,208,128,208s66.57-13.26,91.66-38.34c18.83-18.83,27.3-37.61,27.65-38.4A8,8,0,0,0,247.31,124.76ZM128,192c-30.78,0-57.67-11.19-79.93-33.25A133.47,133.47,0,0,1,25,128,133.33,133.33,0,0,1,48.07,97.25C70.33,75.19,97.22,64,128,64s57.67,11.19,79.93,33.25A133.46,133.46,0,0,1,231.05,128C223.84,141.46,192.43,192,128,192Zm0-112a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Z" />
							</svg>
							{__("View your homepage", "carbonfooter")}
						</Button>
					</div>
				</Modal>
			)}
		</>
	);
};

export default StartModal;
