/* Shared admission choices for Add/Edit forms. Existing records remain available when editing. */
window.IarisAdmissionCatalog = (() => {
    const college = {
        CBEAM: ['BS Accountancy', 'BS Business Administration', 'BS Entrepreneurship', 'BS Legal Management'],
        CEAS: ['Bachelor of Secondary Education', 'BA Multimedia Arts', 'BA Communication', 'BS Psychology', 'BS Biology'],
        CIHTM: ['BS Hospitality Management', 'BS Tourism Management', 'BS Culinary Arts'],
        CITE: ['BS Computer Science', 'BS Information Technology', 'BS Computer Engineering', 'BS Electrical Engineering', 'BS Electronics Engineering', 'BS Industrial Engineering', 'BS Entertainment & Multimedia Computing', 'BS Architecture'],
        CON: ['BS Nursing'], CCJE: ['BS Criminology', 'BS Forensic Science'],
    };
    const school = {
        Preschool: ['Nursery 1', 'Nursery 2', 'Kinder'],
        'Grade School': Array.from({length: 6}, (_, i) => `Grade ${i + 1}`),
        'Junior High': Array.from({length: 4}, (_, i) => `Grade ${i + 7}`),
        'Senior High': ['STEM', 'ABM', 'HUMSS', 'GAS', 'TVL'],
    };
    const graduate = {MBA: 'Master of Business Administration', MEd: 'Master of Education', MIT: 'Master of Information Technology', MPA: 'Master of Public Administration', PhD: 'Doctor of Philosophy in Education'};
    const law = {JD: 'Juris Doctor', LLM: 'Master of Laws', JSD: 'Doctor of Juridical Science'};
    const ipace = ['BS Information Technology', 'BS Computer Science', 'BS Business Administration', 'BS Nursing', 'Bachelor of Elementary Education'];
    const eteeap = ['BS Information Technology', 'BS Business Administration', 'Bachelor of Elementary Education', 'BS Nursing', 'BS Hotel & Restaurant Management'];
    const units = {...college, ...Object.fromEntries(Object.entries(school).map(([k, v]) => [`Integrated School · ${k}`, v])), 'Graduate Programs': Object.values(graduate), 'College of Law': Object.values(law), iPACE: ipace, ETEEAP: eteeap};
    const groups = scope => Object.keys(scope === 'college' ? college : scope === 'is' ? school : scope === 'graduate' ? graduate : scope === 'law' ? law : scope === 'ipace' ? Object.fromEntries(ipace.map(p => [p, []])) : scope === 'eteeap' ? Object.fromEntries(eteeap.map(p => [p, []])) : units);
    const programs = (scope, group) => {
        if (scope === 'graduate') return group === 'PhD' ? ['Doctoral'] : ["Master's"];
        if (scope === 'law') return null; // Prior degree remains a free text field.
        if (scope === 'ipace') return ['Online', 'Blended'];
        if (scope === 'eteeap') return ['Pending', 'Submitted', 'Cleared', 'Not Cleared'];
        return (scope === 'college' ? college : scope === 'is' ? school : units)[group] || [];
    };
    return {groups, programs, units};
})();
