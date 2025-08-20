/**
 * Calculate number of trees needed to offset CO2 emissions
 *
 * Formula: 1 tree absorbs 5900g CO2 per year
 *
 * @param {number} emissions - CO2 emissions in grams
 * @returns {number} Number of trees needed (rounded up)
 */
export const calculateTreesNeeded = (emissions) => {
	if (typeof emissions !== "number" || emissions < 0) {
		return 0;
	}

	// Convert emissions to trees needed (1 tree = 5900g CO2 per year)
	const trees = emissions / 5900;

	// Round up to the nearest whole tree
	return Math.ceil(trees);
};

/**
 * Format trees needed as a string
 *
 * @param {number} emissions - CO2 emissions in grams
 * @returns {string} Formatted trees string
 */
export const formatTreesNeeded = (emissions) => {
	const trees = calculateTreesNeeded(emissions);
	return `${trees} trees`;
};

/**
 * Get trees needed
 *
 * @param {number} emissions - CO2 emissions in grams
 * @returns {number} Number of trees needed (rounded up)
 */
export const getTreesNeeded = (emissions) => {
	return calculateTreesNeeded(emissions);
};
