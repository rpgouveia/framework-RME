const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    // Laravel serializes `date` casts as midnight UTC, so formatting in the
    // viewer's timezone could shift the day.
    timeZone: 'UTC',
});

/** Formats an ISO date string from the backend, e.g. `15 de jan. de 2026`. */
export function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

/**
 * Value for an `<input type="date">`: the date part of a backend ISO string,
 * or today in the viewer's timezone when no value is given.
 */
export function dateInputValue(value?: string | null): string {
    if (value) {
        return value.slice(0, 10);
    }

    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}
