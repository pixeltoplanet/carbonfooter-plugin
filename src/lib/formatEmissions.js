export const formatEmissions = (emissions) => {
	// If emissions are greater than 1000, convert to kg
	if (emissions > 1000) {
		return `${(emissions / 1000).toFixed(2)} kg`;
	}

	return `${emissions.toFixed(2)} gr`;
};
