from .models import Familiarisation


def get_familiarisations(vatsim_id: int) -> dict:
    from collections import defaultdict

    user_familiarisations = Familiarisation.objects.filter(
        user__username=vatsim_id
    ).select_related("sector")

    # Step 1: Group into normal dict
    familiarisations_by_fir = defaultdict(list)

    for fam in user_familiarisations:
        familiarisations_by_fir[fam.sector.fir].append(fam)

    # Step 2: Sort sectors alphabetically within each FIR
    familiarisations_by_fir_sorted = {}
    for fir, fam_list in familiarisations_by_fir.items():
        sorted_fam_list = sorted(fam_list, key=lambda f: f.sector.name)
        familiarisations_by_fir_sorted[fir] = sorted_fam_list

    # Step 3: Sort the FIRs alphabetically
    familiarisations_by_fir_sorted = dict(
        sorted(familiarisations_by_fir_sorted.items(), key=lambda x: x[0])
    )

    return familiarisations_by_fir_sorted
