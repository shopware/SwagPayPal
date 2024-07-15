import shipmentCarrier from './shipment_carrier.json';

export const commonCarriers: string[] = [
    'ARAMEX',
    'AU_AU_POST',
    'CA_CANADA_POST',
    'CN_CHINA_POST_EMS',
    'CORREOS_DE_MEXICO',
    'DEUTSCHE_DE',
    'DHL',
    'DPD',
    'FEDEX',
    'GLS',
    'GENERAL_OVERNIGHT',
    'HERMES',
    'JPN_JAPAN_POST',
    'LA_POSTE_SUIVI',
    'ONTRAC',
    'IT_POSTE_ITALIA',
    'NLD_POSTNL',
    'ROYAL_MAIL',
    'SF_EX',
    'TNT',
    'UPS',
    'USPS',
    'YODEL',
    'OTHER',
];

export const descriptionPatch: Record<string, string> = {
    CORREOS_DE_MEXICO: 'Correos',
    DEUTSCHE_DE: 'Deutsche Post',
    DHL: 'DHL',
    FEDEX: 'FedEx',
    GENERAL_OVERNIGHT: 'Go!',
    HERMES: 'Hermes',
    ONTRAC: 'OnTrac',
    IT_POSTE_ITALIA: 'Poste Italiane',
    NLD_POSTNL: 'PostNL',
    ROYAL_MAIL: 'Royal Mail',
    SF_EX: 'SF Express (Shun Feng Express)',
    UPS: 'UPS',
    USPS: 'USPS',
    YODEL: 'Yodel',
};

const stripHTML = Shopware.Filter.getByName('striphtml');

const carriers = shipmentCarrier['x-enum'].map((carrier) => {
    return {
        value: carrier.value,
        description: descriptionPatch[carrier.value] || stripHTML(carrier.description).replace(/\.$/g, ''),
    };
});

// Sort carriers by description, but put common carriers on top
carriers.sort((a, b) => {
    const aIncluded = commonCarriers.includes(a.value);
    const bIncluded = commonCarriers.includes(b.value);

    if (aIncluded && !bIncluded) {
        return -1;
    }

    if (!aIncluded && bIncluded) {
        return 1;
    }

    return a.description.localeCompare(b.description);
});

carriers.splice(commonCarriers.length - 1, 0, {
    value: 'OTHER',
    description: 'Other',
});

export default carriers;
