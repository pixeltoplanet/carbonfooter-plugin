/**
 * Calculate driving distance equivalent from CO2 emissions
 *
 * Formula: 1g CO2 = 5 meters = 0.005 km
 *
 * @param {number} emissions - CO2 emissions in grams
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {number} Driving distance in kilometers
 */
export const calculateDrivingDistance = (emissions, decimals = 2) => {
	if (typeof emissions !== "number" || emissions < 0) {
		return 0;
	}

	// Convert emissions to driving distance (1g CO2 = 0.005 km)
	const distance = emissions * 0.005;

	// Round to specified decimal places
	return Number(distance.toFixed(decimals));
};

/**
 * Format driving distance in kilometers
 *
 * @param {number} emissions - CO2 emissions in grams
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} Formatted driving distance string in kilometers
 */
export const formatDrivingDistance = (emissions, decimals = 2) => {
	const distance = calculateDrivingDistance(emissions, decimals);
	return `${distance}km`;
};

/**
 * Get driving distance in kilometers
 *
 * @param {number} emissions - CO2 emissions in grams
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {object} Object with distance in kilometers
 */
export const getDrivingDistance = (emissions, decimals = 2) => {
	const distance = calculateDrivingDistance(emissions, decimals);

	return `${distance}km`;
};
