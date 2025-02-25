export function applyDayStyles(dayElem, stylingAttribute = '', target =''){
    if (target === 'bekijkaanvraag'){
        dayElem.classList.add('calendar-day');
        if (stylingAttribute == 1){
            dayElem.setAttribute('title', 'datum met 1 boeking');
        } else if (stylingAttribute > 1) {
            dayElem.setAttribute('title', 'datum met 2 of meerdere boekingen');
        }
        if(stylingAttribute){
            if (stylingAttribute > 1) {
                dayElem.classList.add('multiple-schools', 'extraLayer');
            } else {
                dayElem.classList.add('single-school', 'extraLayer');
            }
        }
    } else if (target === 'blokkeer' ){
        dayElem.classList.add('calendar-day');
        let dateString= dayElem.getAttribute('aria-label');
        dayElem.setAttribute('title', `blokkeer ${dateString}`);
        dayElem.classList.add('single-school', 'extraLayer');
    } else if (target === 'weekaanvragen') {
        if (stylingAttribute === 'maandag'){
        dayElem.classList.add('calendar-day');
        dayElem.setAttribute('title', `Week met lopende boekingen`);
        dayElem.classList.add('multiple-schools', 'extraLayer');
        } else {
        dayElem.classList.add('calendar-day');
        let dateString= dayElem.getAttribute('aria-label');
        dayElem.setAttribute('title', `${dateString}`);
        dayElem.classList.add('single-school', 'extraLayer');
        }

    } else if (target === 'downloaddataset'){
        dayElem.classList.add('calendar-day');
        let dateString= dayElem.getAttribute('aria-label');
        dayElem.setAttribute('title', `selecteer ${dateString}`);
        dayElem.classList.add('single-school', 'extraLayer');

    } else {
        return;
    }
    
}

export function resetDayStyles(dayElem) {
    Object.assign(dayElem.style, {
        backgroundColor: '',
        color: '',
        border: '',
        borderRadius: '',
        padding: '',
        width: '',
        height: '',
        display: '',
        alignItems: '',
        justifyContent: '',
        fontWeight: '',
        boxShadow: '',
        transform: ''
    });
}

export function formatBezoekdatum(dateString) {
    const maanden = [
        'januari', 'februari', 'maart', 'april', 'mei', 'juni',
        'juli', 'augustus', 'september', 'oktober', 'november', 'december'
    ];

    // Omzetten van een datumstring naar een Date object
    const date = new Date(dateString);
    const dag = String(date.getDate()).padStart(2, '0');
    const maandNaam = maanden[date.getMonth()];
    const jaar = date.getFullYear();

    // Datum formatteren naar "dd maand jjjj"
    return `${dag} ${maandNaam} ${jaar}`;
}

export function getISODate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
