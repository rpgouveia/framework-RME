/**
 * Rules the link form uses to offer only links the server would accept.
 *
 * Ids are compared as strings: a Select hands back strings while the server
 * sends numbers, and a strict comparison between the two never matches.
 */

type Id = number | string;

const sameId = (a: Id, b: Id): boolean => String(a) === String(b);

/** Which part of the Saeri taxonomy the form shows; null means all. */
export type CatalogueFilter = {
    category: string | null;
    subcategory: string | null;
};

/**
 * The catalogue entries a risk can still be linked to, within a Saeri
 * category or subcategory, or across all of them.
 *
 * A mitigation the risk is already linked to is left out: the pair can
 * exist only once (R-6). With no risk chosen yet, nothing is linked.
 */
export function availableMitigations<
    T extends {
        id: Id;
        category: string | null;
        subcategory: { code: string };
    },
>(
    risk: { linked_mitigation_ids: Id[] } | undefined,
    catalogue: T[],
    filter: CatalogueFilter,
): T[] {
    const linked = risk?.linked_mitigation_ids ?? [];

    return catalogue.filter(
        (mitigation) =>
            !linked.some((id) => sameId(id, mitigation.id)) &&
            (filter.category === null ||
                mitigation.category === filter.category) &&
            (filter.subcategory === null ||
                mitigation.subcategory.code === filter.subcategory),
    );
}
