/**
 * Rules the link form uses to offer only links the server would accept.
 *
 * Ids are compared as strings: a Select hands back strings while the server
 * sends numbers, and a strict comparison between the two never matches.
 */

type Id = number | string;

const sameId = (a: Id, b: Id): boolean => String(a) === String(b);

/**
 * The catalogue entries a risk can still be linked to, within a SAERI
 * category or across all of them.
 *
 * A mitigation the risk is already linked to is left out: the pair can
 * exist only once (R-6). With no risk chosen yet, nothing is linked.
 */
export function availableMitigations<
    T extends { id: Id; saeri_category: string },
>(
    risk: { linked_mitigation_ids: Id[] } | undefined,
    catalogue: T[],
    category: string,
): T[] {
    const linked = risk?.linked_mitigation_ids ?? [];

    return catalogue.filter(
        (mitigation) =>
            !linked.some((id) => sameId(id, mitigation.id)) &&
            (category === 'all' || mitigation.saeri_category === category),
    );
}
