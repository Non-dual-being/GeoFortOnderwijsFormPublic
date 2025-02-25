/**||-------------------------------------[constanten]--------------------------------------------------- */
const OnderwijsniveauSelect = document.getElementById('onderwijsNiveau')  ?? null; //gekozen schooltype
const niveauDiv = document.getElementById('niveauDiv') ?? null;
const niveauOptions = document.getElementById('onderwijsSchooltypeNiveau') ?? null;
const niveauFout = document.getElementById('niveauOptionsFout') ?? null;
const leeftijdDivPO = document.getElementById('leeftijdDivPO') ?? null;
const leeftijdOptionsPO = document.getElementById('onderwijsSchooltypeNiveauLeeftijdsGroepenPO')?? null;
const leeftijdOptionsPOFout = document.getElementById('onderwijsSchooltypeNiveauLeeftijdsGroepenPOFout')?? null;
const leeftijdDivVO = document.getElementById('leeftijdDivVO')?? null;
const leeftijdDivVOFout = document.getElementById('onderwijsSchooltypeNiveauLeeftijdsGroepenFoutVO')?? null;
const calenderDataFromPHP = window.calenderData ?? []; //agendadata;
const contactPersoonVoornaam = document.getElementById('contactpersoonvoornaam') ?? null;
const contactPersoonVoornaamFoutElement = document.getElementById('contactpersoonvoornaamFout');
const contactPersoonAchternaam = document.getElementById('contactpersoonachternaam')
const contactPersoonAchternaamFoutElement = document.getElementById('contactpersoonachternaamFout')
const email = document.getElementById('emailadres');4
const emailFout = document.getElementById('emailadresFout');
const telefoonNummer = document.getElementById('schoolTelefoonnummer');
const telefoonNummerFout = document.getElementById('schoolTelefoonnummerFout');
const telefoonNummerContact = document.getElementById('contactTelefoonnummer');
const telefoonNummerContactFout = document.getElementById('contactTelefoonnummerFout');
const schoolnaam = document.getElementById('schoolnaam');
const schoolnaamFout = document.getElementById('schoolnaamFout');
const aantalLeerlingenInput = document.getElementById('aantalLeerlingen');
const aantalLeerlingenInputFout = document.getElementById('aantalLeerlingenFout');
const aantalBegeleidersInput = document.getElementById('totaalBegeleiders');
const aantalBegeleidersInputFout = document.getElementById('totaalBegeleidersFout');
const adres = document.getElementById('adres');
const adresFout = document.getElementById('adresFout');
const postcode = document.getElementById('postcode');
const postcodeFout = document.getElementById('postcodeFout');
const plaats = document.getElementById('plaats');
const plaatsFout = document.getElementById('plaatsFout');
const keuzeModule = document.getElementById('keuzeModule');
const keuzeModuleFout = document.getElementById('keuzeModuleFout');
const kentGeoFort = document.getElementById('hoekentGeoFort');
const kentGeoFortFout = document.getElementById('hoekentGeoFortFout');
const vragenOpmerkingen = document.getElementById('vragenOpmerkingen');
const vragenOpmerkingenFout = document.getElementById('vragenOpmerkingenFout');
const textarea = document.getElementById('vragenOpmerkingen');
const tekenTeller = document.getElementById('tekenTeller');
const maxTekens = 600;
const aantalLeerlingenVeld = document.getElementById('aantalLeerlingen');
const toggles = document.querySelectorAll(".meerInformatieToggle");
const foodInputs = document.querySelectorAll('input[type="checkbox"][name="snack"], input[type="checkbox"][name="lunch"]');
const Form = document.getElementById('onderwijsFormulier');

//timer let
let foutmeldingsTimer = null;
let hoverTimeout;
let hoverTimeout_agenda; // Variabele voor het bijhouden van de timeout voor de agenda
let hoverMessageElement_agenda; // Variabele voor de huidige hover-message van de agenda



const onderwijsModules = {
    primairOnderwijs: {
        standaard: ["Klimaat-Experience", "Klimparcours", "Voedsel-Innovatie", "Dynamische-Globe"],
        keuze: ["Minecraft-Klimaatspeurtocht", "Earth-Watch","Stop-de-Klimaat-Klok", "Minecraft-Programmeren"]
    },
    voortgezetOnderbouw: {
        standaard: ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch"],
        keuze: ["Minecraft-Windenergiespeurtocht", "Stop-de-Klimaat-Klok","Minecraft-Programmeren"]
    },
    voortgezetBovenbouw: {
        standaard: ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch"],
        keuze: ["Crisismanagement"]
    }
};

const onderwijsNiveaus = {
    primairOnderwijs: ["regulier", "speciaal"],
    voorgezetOnderwijs: ["VMBO", "MAVO", "HAVO", "VWO", "Praktijk-Onderwijs"]
}


const leeftijden = {
    primairOnderwijs: {
        regulier: ['Groep 4', 'Groep 5', 'Groep 6', 'Groep 7', 'Groep 8'],
        speciaal: ['Groep 4', 'Groep 5', 'Groep 6', 'Groep 7', 'Groep 8']
    }, 
    voortgezetOnderbouw: {
        VMBO_BB_KB: ['VMBO 1', 'VMBO 2', 'VMBO 3'],
        VMBO_GL_TL: ['VMBO 1', 'VMBO 2', 'VMBO 3'],
        HAVO: ['HAVO 1', 'HAVO 2', 'HAVO 3'],
        VWO: ['Atheneum 1', 'Atheneum 2', 'Atheneum 3', 'Gymnasium 1', 'Gymnasium 2', 'Gymnasium 3'],
        PraktijkOnderwijs: ['Praktijk Onderwijs 1', 'Praktijk Onderwijs 2', 'Praktijk Onderwijs 3']
    },
    voortgezetBovenbouw: {
        VMBO: ['VMBO 4', 'VMBO 5', 'VMBO 6'],
        MAVO: ['MAVO 4'],
        HAVO: ['HAVO 4', 'HAVO 5'],
        VWO: ['Atheneum 4', 'Atheneum 5', 'Atheneum 6', 'Gymnasium 4', 'Gymnasium 5', 'Gymnasium 6'],
        PraktijkOnderwijs: ['Praktijk Onderwijs 4', 'Praktijk Onderwijs 5']
    }
};


/**||--------------[Functies]------------------------------|| */


//function to extract a value or file from the form

function getGekozenOnderwijsNiveau() {
    const OnderwijsniveauValue = OnderwijsniveauSelect.value;
    let gekozenOnderwijsNiveau = "";
    OnderwijsniveauValue === "primairOnderwijs"
        ? gekozenOnderwijsNiveau = "basis"
        : gekozenOnderwijsNiveau = "voortgezet";
    return gekozenOnderwijsNiveau;
}

function getNiveauValues () {
    const gekozenOnderwijsNiveau = getGekozenOnderwijsNiveau();              
    if (gekozenOnderwijsNiveau === "basis") {
        if (niveauOptions) {
            // Zoek de eerste geselecteerde optie
            const geselecteerdeOptie = Array.from(niveauOptions.options)
                .find(option => option.selected); // Vind de eerste geselecteerde optie
            
            // Retourneer de waarde van de geselecteerde optie of null als er niets is geselecteerd
            return geselecteerdeOptie ? geselecteerdeOptie.value : null;
        }

    } else if (gekozenOnderwijsNiveau === "voortgezet") {
    if (niveauOptions) {
        const geselecteerdeOptions = Array.from(niveauOptions.selectedOptions).map(option => option.value);
            if (geselecteerdeOptions.length  === 0) {
                return [];
    } else if (geselecteerdeOptions.length >= 1 && geselecteerdeOptions.length <= 3) {
                return geselecteerdeOptions;
        } else return null;
    }
}

}


function getLeeftijdsgroepenPOValues () {
    const gekozenOnderwijsNiveau = getGekozenOnderwijsNiveau();
    const geselecteerdNiveau = getNiveauValues();    
    if (gekozenOnderwijsNiveau === "basis") {
        const geldigeNiveauOptions = Object.keys(leeftijden[OnderwijsniveauSelect.value])
        if (geldigeNiveauOptions.includes(geselecteerdNiveau) && leeftijdOptionsPO) {
            const gekozenLeeftijdsGroepenPO = Array.from(leeftijdOptionsPO.selectedOptions).map(option => option.value);
            return gekozenLeeftijdsGroepenPO
        } else return null
    } else return null
}


const getRooster = async (onderwijsNiveau, keuzeModule, aantalLeerlingen3) => {
    const roosterAfbeeldingContainer = document.getElementById('roosterAfbeeldingContainer');
    const roosterAfbeelding = document.getElementById('roosterAfbeelding');
    const downloadButton = document.getElementById('downloadRooster');

    try{

        const formData = new URLSearchParams();
        formData.append('schooltype', onderwijsNiveau);
        formData.append('lesmodule', keuzeModule);
        formData.append('aantalleerlingen', aantalLeerlingen3);

        const response = await fetch('Get_Rooster.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })

            if (!response.ok){
                throw new Error('afbeelding niet beschikbaar');
            }
       
            const responseData = await response.json();
            if (responseData.success){
                roosterAfbeeldingContainer.style.display = 'block';
                roosterAfbeelding.src = responseData.afbeelding;
                downloadButton.style.display = 'block'; 
                
                downloadButton.onclick = function (event) {
                    event.preventDefault();
                    const link = document.createElement('a');
                    link.href = responseData.pdf;
                    link.target = '_blank';
                    link.click();
                };
            } else if(responseData && responseData.afbeeldingAlter && typeof responseData.afbeeldingAlter === 'string' && responseData.afbeeldingAlter.length) {
                roosterAfbeeldingContainer.style.display = 'block';
                roosterAfbeelding.src = responseData.afbeeldingAlter;
                downloadButton.style.display = 'none';

            } else {
                throw new Error('Geen afbeelding beschikbaar');
            }

    } catch(error){
        roosterAfbeeldingContainer.style.display = 'none';
        downloadButton.style.display = 'none';           
    }
}

function calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput) {
    const aantalLeerlingen = parseInt(aantalLeerlingenInput.value, 10) || 0;
    const totaalBegeleiders = parseInt(aantalBegeleidersInput.value, 10) || 0;
    let prijsPerLeerling = "";
    const gekozenNiveau = getGekozenOnderwijsNiveau();
    gekozenNiveau === "basis" ? prijsPerLeerling = 18 : prijsPerLeerling = 22;


    const gratisBegeleiders = Math.floor(aantalLeerlingen / 8);
    const teBetalenBegeleiders = Math.max(0, totaalBegeleiders - gratisBegeleiders);

    const bezoekPrijs = (aantalLeerlingen * prijsPerLeerling) + (teBetalenBegeleiders * prijsPerLeerling);
    return [bezoekPrijs.toFixed(2),aantalLeerlingen,teBetalenBegeleiders,prijsPerLeerling];
}

//--------functions to update a value from the form or apply a style

// Functie om het label dynamisch aan te passen
const updateNiveauLabel = (onderwijsniveau) => {
    const niveauLabel = document.querySelector('label[for="onderwijsSchooltypeNiveau"]');
    if (onderwijsniveau === "primair") {
    niveauLabel.innerHTML ='<label for="onderwijsSchooltypeNiveau">Kies wat van toepassing is</label>';
    } else if (onderwijsniveau === "voortgezet") {   
        niveauLabel.innerHTML = '<label for="onderwijsSchooltypeNiveau"><strong><span class="highlighted-text-info-PO" data-info-tip="Klik om meerdere selecties te maken">Kies</span></strong> 1 tot max 3 niveau\'s</label>'
    }
}

// Update tekenteller
function updateTekenTeller() {
    const resterendeTekens = maxTekens - textarea.value.length;
    tekenTeller.textContent = resterendeTekens + ' tekens over';

    // Als resterende tekens 0 is, blokkeer extra invoer
    if (resterendeTekens <= 0) {
        textarea.value = textarea.value.substring(0, maxTekens);  // Verwijder extra invoer
        tekenTeller.textContent = '0 tekens over'; // Zorg dat dit altijd correct is
    }
}

function werkKeuzeModulesBij1() {
    const onderwijsNiveau1 = OnderwijsniveauSelect.value || 'primairOnderwijs';
    const keuzeModuleSelect1 = document.getElementById('keuzeModule');

    keuzeModuleSelect1.innerHTML = '';

    if (onderwijsNiveau1 in onderwijsModules) {
        onderwijsModules[onderwijsNiveau1].keuze.forEach(module1 => {
            const optie1 = document.createElement('option');
            optie1.value = module1;
            optie1.textContent = module1;
            keuzeModuleSelect1.appendChild(optie1);
        });
    }

    werkGeselecteerdeModulesWeergaveBij2();
}

function werkGeselecteerdeModulesWeergaveBij2() {
    const onderwijsNiveau2 = OnderwijsniveauSelect.value || 'primairOnderwijs';
    const keuzeModule2 = document.getElementById('keuzeModule').value;

    const standaardModulesLijst2 = document.getElementById('standaardModulesLijst');
    standaardModulesLijst2.innerHTML = '';
    if (onderwijsNiveau2 in onderwijsModules) {
        onderwijsModules[onderwijsNiveau2].standaard.forEach(module2 => {
            const lijstItem2 = document.createElement('li');
            lijstItem2.textContent = module2;
            standaardModulesLijst2.appendChild(lijstItem2);
        });
    }

    const gekozenKeuzeModule2 = document.getElementById('gekozenKeuzeModule2');
    gekozenKeuzeModule2.style.listStyleType = 'disc';
    gekozenKeuzeModule2.textContent = keuzeModule2 || 'Geen keuzevak geselecteerd';

    return standaardModulesLijst2;
}

function haalRoosterOp() {
    const onderwijsNiveau = OnderwijsniveauSelect.value;
    const keuzeModule = document.getElementById('keuzeModule').value;
    const aantalLeerlingen3 = parseInt(document.getElementById('aantalLeerlingen').value, 10);
    let groepAantal3 = '';

    if (aantalLeerlingen3 >= 40 && aantalLeerlingen3 <= 50) {
        groepAantal3 = 3;
    } else if (aantalLeerlingen3 >= 51 && aantalLeerlingen3 <= 65) {
        groepAantal3 = 4;
    } else if (aantalLeerlingen3 >= 66 && aantalLeerlingen3 <= 80) {
        groepAantal3 = 5;
    } else if (aantalLeerlingen3 >= 81 && aantalLeerlingen3 <= 100) {
        groepAantal3 = 6;
    } else if (aantalLeerlingen3 >= 101 && aantalLeerlingen3 <= 120) {
        groepAantal3 = 7;
    } else if (aantalLeerlingen3 >= 121 && aantalLeerlingen3 <= 130) {
        groepAantal3 = 8;
    } else if (aantalLeerlingen3 >= 131 && aantalLeerlingen3 <= 150) {
        groepAantal3 = 9;
    } else if (aantalLeerlingen3 >= 151 && aantalLeerlingen3 <= 160) {
        groepAantal3 = 10;
    }

    document.getElementById('groepAantal').textContent = groepAantal3;

    if (onderwijsNiveau && keuzeModule && aantalLeerlingen3 >= 40 && aantalLeerlingen3 <= 160) {
        getRooster(onderwijsNiveau, keuzeModule, aantalLeerlingen3);
    }
}

function valideerAantalLeerlingenInvoer() {
    const aantalLeerlingenVeld = aantalLeerlingenInput;
    const aantalLeerlingen = aantalLeerlingenVeld.value;
    const foutMelding = aantalLeerlingenInputFout;

    // Roep de validatiefunctie aan
    const validatieFout = valideerAantalLeerlingen(aantalLeerlingen);

    if (validatieFout) {
        foutMelding.textContent = validatieFout;
        foutMelding.style.display = 'block'; // Toon de foutmelding
        aantalLeerlingenVeld.setCustomValidity(validatieFout); // Stel de custom validatie in

        aantalLeerlingenVeld.classList.add('foute-invoermelding__errorField', 'extraLayer');

        /**customvalidity is een manier om bij het verzenden de de browser te laten weten dat er een fout zit en die te laten tonen */

        // Stijl aanpassen bij fout

        aantalLeerlingenInputFout.scrollIntoView({behavior:"smooth", block: "center"});
        
        return false;
        } else {
        foutMelding.textContent = "";
        foutMelding.style.display = 'none'; // Verberg de foutmelding
        aantalLeerlingenVeld.setCustomValidity(''); // Reset de custom validatie
        aantalLeerlingenVeld.classList.remove('foute-invoermelding__errorField', 'extraLayer');
        aantalBegeleidersInput.disabled = false;
        valideerAantalBegeleidersInvoer();
        aantalBegeleidersInput.focus();

        // Roep de rooster-ophaal functie aan wanneer de invoer correct is
        haalRoosterOp();
        return true;
    }
}

function valideerAantalBegeleidersInvoer(){
    let aantalLeerlingenCheck;

    if (aantalLeerlingenInput.value === null || aantalLeerlingenInput.value === ""){
        valideerAantalLeerlingenInvoer();
        aantalBegeleidersInput.disabled = true;
        aantalBegeleidersInputFout.textContent = "";
        aantalBegeleidersInput.value = "";
        aantalBegeleidersInputFout.classList.remove('toonFoutMeldingBlock');
        aantalBegeleidersInput.classList.remove('foute-invoermelding__errorField', 'extraLayer', 'foute-invoermelding__focusinfoField');
        return;
    }

    if (aantalLeerlingenInput.value > 39 && aantalBegeleidersInput.value < 161){
        aantalLeerlingenCheck = true;
    } else {
        valideerAantalBegeleiders();
    }

    if (aantalLeerlingenCheck){
        aantalBegeleidersInput.disabled = false;
        const aantalBegeleidersCheck = valideerAantalBegeleiders(aantalBegeleidersInput.value);

        if (aantalBegeleidersCheck){
            aantalBegeleidersInputFout.textContent = aantalBegeleidersCheck;
            aantalBegeleidersInputFout.classList.add('toonFoutMeldingBlock');
            if (aantalBegeleidersInput.value === "" || aantalBegeleidersInput.value === null){
                aantalBegeleidersInput.classList.add('foute-invoermelding__focusinfoField', 'extraLayer');
            } else {
                aantalBegeleidersInput.classList.add('foute-invoermelding__errorField', 'extraLayer');
            }
            aantalBegeleidersInput.scrollIntoView({behavior:"smooth", block: "center"});  
            return false;        

        } else {
            aantalBegeleidersInputFout.textContent = "";
            aantalBegeleidersInputFout.classList.remove('toonFoutMeldingBlock');
            aantalBegeleidersInput.classList.remove('foute-invoermelding__errorField', 'extraLayer', 'foute-invoermelding__focusinfoField');
            return true;
        }

    } 

}



function updateMeerInformatieLink() {
    const onderwijsNiveau = OnderwijsniveauSelect.value;
    const meerInformatieLink = document.querySelector('.meerInformatieToggle[data-target="onderwijsNiveauInfo"] span');



    const onderwijsNiveauMapping = {
        primairOnderwijs: 'Primair Onderwijs',
        voortgezetOnderbouw: 'Voortgezet Onderwijs - Onderbouw',
        voortgezetBovenbouw: 'Voortgezet Onderwijs - Bovenbouw'
    };

    // Verkrijg de volledige naam op basis van de gekozen waarde
    const volledigeNaam = onderwijsNiveauMapping[onderwijsNiveau] || onderwijsNiveau;

    meerInformatieLink.textContent = `Bekijk de standaard lesmodules van ${volledigeNaam}`;
    
    const weergaveStandaardModules = document.getElementById("weergaveStandaardModules");
    weergaveStandaardModules.innerText = "";
    const standaardModules = werkGeselecteerdeModulesWeergaveBij2().children;

    // Voeg standaardmodules toe
    for (let i = 0; i < standaardModules.length; i++) {
        weergaveStandaardModules.appendChild(standaardModules[i].cloneNode(true));
    }
}


function updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput) {
    let totalPrice = 0;
    let foodPrice = 0;

    const btwPercentage_bezoek = 9; // Btw percentage
    const btwPercentage_food = 9;
    const totalPriceSpan = document.getElementById('totalPrice');
    const foodSummaryDiv = document.getElementById('foodSummary');
    const bezoekDiv = document.getElementById('bezoek');
    
    foodSummaryDiv.innerHTML = '';
    bezoekDiv.innerHTML = '';

    const bezoekPrice = parseFloat(calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput)[0]);
    const aantalLeerlingen = calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput)[1];
    const teBetalenBegeleiders = calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput)[2];
    const prijsperLeerling = calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput)[3];
    const prijsLeerlingen = aantalLeerlingen * prijsperLeerling;

    if (aantalLeerlingen > 0) {
        const leerlingSummary = document.createElement('div');
        leerlingSummary.className = 'summary-item';
        // Gebruik toLocaleString voor de weergave
        leerlingSummary.innerHTML = `<span>${aantalLeerlingen} leerlingen:</span><span>€ ${prijsLeerlingen.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
        bezoekDiv.appendChild(leerlingSummary);
    }

    if (teBetalenBegeleiders > 0) {
        const begeleidersPrijs = teBetalenBegeleiders * 20;
        const begeleiderSummary = document.createElement('div');
        begeleiderSummary.className = 'summary-item';
        begeleiderSummary.innerHTML = `<span>${teBetalenBegeleiders} te betalen begeleiders:</span><span>€ ${begeleidersPrijs.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
        bezoekDiv.appendChild(begeleiderSummary);
    }

    const bezoekTotalSummary = document.createElement('div');
    bezoekTotalSummary.className = 'summary-item';
    bezoekTotalSummary.innerHTML = `<span style="font-size: 1.2em;"><strong>Totale bezoekprijs:</strong></span><span style="font-size: 1.2em;"><strong>€ ${bezoekPrice.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></span>`;
    bezoekDiv.appendChild(bezoekTotalSummary);

    const totalExclBtw_bezoek = bezoekPrice / (1 + btwPercentage_bezoek / 100);
    const exclBtwSummary_bezoek = document.createElement('div');
    exclBtwSummary_bezoek.className = 'summary-item';
    exclBtwSummary_bezoek.innerHTML = `<span style="font-size: 0.9em;">Totale bezoekprijs excl. btw (9%):</span><span style="font-size: 0.9em;"> € ${totalExclBtw_bezoek.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
    bezoekDiv.appendChild(exclBtwSummary_bezoek);

    foodInputs.forEach(input => {
        const amountInput = input.closest('.snack-option').querySelector('input[type="number"]');
        if (input.checked && amountInput && amountInput.value > 0) {
            const itemTotal = (input.value * amountInput.value);
            foodPrice += itemTotal;
            totalPrice += itemTotal;
            const itemSummary = document.createElement('div');
            itemSummary.className = 'summary-item';
            const itemName = input.labels[0].innerText.split(':')[0];
            itemSummary.innerHTML = `<span>${amountInput.value} ${itemName}:</span><span>€ ${itemTotal.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
            foodSummaryDiv.appendChild(itemSummary);
        }
    });

    const foodTotalSummary = document.createElement('div');
    foodTotalSummary.className = 'summary-item';
    foodTotalSummary.innerHTML = `<span style="font-size: 1.2em;"><strong>Totale bestelprijs:</strong></span><span style="font-size: 1.2em;"><strong>€ ${foodPrice.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></span>`;
    foodSummaryDiv.appendChild(foodTotalSummary);

    const totalExclBtwfood = foodPrice / (1 + btwPercentage_food / 100);
    const exclBtwSummary_food = document.createElement('div');
    exclBtwSummary_food.className = 'summary-item';
    exclBtwSummary_food.innerHTML = `<span style="font-size: 0.9em;">Totale bestelprijs excl. btw (9%):</span><span style="font-size: 0.9em;">€ ${totalExclBtwfood.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
    foodSummaryDiv.appendChild(exclBtwSummary_food);

    totalPrice += bezoekPrice;
    const totalExlBtw = parseFloat(totalExclBtw_bezoek + totalExclBtwfood);
    totalPriceSpan.textContent = ` ${totalPrice.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const exlBTWTotalDiv = document.createElement('div');
    exlBTWTotalDiv.className = 'summary-item';
    exlBTWTotalDiv.innerHTML = `<span style="font-size: 0.6em;"><strong>Totale prijs excl. BTW:</strong></span><span style="font-size: 0.6em;"><strong>€ ${totalExlBtw.toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></span>`;
    totalPriceSpan.appendChild(exlBTWTotalDiv);

    return [bezoekPrice, foodPrice, totalPrice];
}


function applyCommonStyles(element, backgroundColor) {
    element.classList.add('foutmeldingStylingFunctie')
    element.style.backgroundColor = backgroundColor; 
}

// Algemene functie om foutmeldingen te tonen met display
// Functie om foutmeldingen te tonen met inline styling
function toonFoutmelding(foutElement, foutmelding, element, duur = 6000) {
    foutElement.textContent = foutmelding;
    foutElement.classList.add("toonFoutMelding"); 
    const rect = element.getBoundingClientRect();
    const scrollY = window.scrollY;
    if (element.id !== 'prijs'){
        foutElement.style.left = `${rect.left + window.scrollX}px`;
        foutElement.style.top = `${rect.top + scrollY - foutElement.offsetHeight - 5}px`;
    } else {
        foutElement.classList.add('relativePos');
    }
    foutElement.scrollIntoView({behavior:"smooth", block: "center"});

    // de timer resetten en zorgen dat de foutmelding van de lopende timer wel verdwijnt voordat de nieuwe begint
    if (foutmeldingsTimer) {
        setTimeout(() => {
            hideFoutmelding(foutElement); // Verberg onmiddellijk de vorige foutmelding
        }, 3000)   
     
    } else {
        foutmeldingsTimer = setTimeout(() => {
            hideFoutmelding(foutElement)
        }, duur);
    }


}

// Verberg foutmelding en herstel de styling van het invoerveld
function hideFoutmelding(foutElement) {
    setTimeout(() => {
        foutElement.textContent = ""; // Wis de foutmelding
        foutElement.className = 'foute-invoermelding';
    }, 300);
    foutmeldingsTimer = null;
}



function scheduleHoverMessage4(message, element, className = 'hover-message') {
    clearTimeout(hoverTimeout);
    hoverTimeout = setTimeout(() => showHoverMessage6(message, element, className), 200);
}

function cancelHoverMessage5() {
    clearTimeout(hoverTimeout);
    hideHoverMessage7();
}


function showHoverMessage6(message, element, className = 'hover-message') {
    let hoverMessageElement;

    // Voor agenda-specifieke hover-messages
    if (className === 'hover-message-agenda') {
        // Verberg de huidige agenda hover-message als die nog actief is
        

        const calendarContainer = document.querySelector('.flatpickr-calendar');
        hoverMessageElement_agenda = calendarContainer.querySelector(`.${className}`);

        // Als het element nog niet bestaat, maak het aan
        if (!hoverMessageElement_agenda) {
            hoverMessageElement_agenda = document.createElement('div');
            hoverMessageElement_agenda.className = className;
            calendarContainer.appendChild(hoverMessageElement_agenda);
        }

        // Stel de agenda hover-message in
        hoverMessageElement_agenda.textContent = message;
        hoverMessageElement_agenda.className = 'hover-message-agenda';
        hoverMessageElement_agenda.style.display = 'block';
        hoverMessageElement_agenda.style.opacity = '1';
    


        // Clear bestaande timeout en stel een nieuwe in
        clearTimeout(hoverTimeout_agenda);
    hoverTimeout_agenda = setTimeout(() => {
        hoverMessageElement_agenda.style.opacity = '0'; // Start de fade-out
        setTimeout(() => {
            hoverMessageElement_agenda.style.display = 'none'; // Verberg volledig na de fade-out
        }, 600); // Na de overgang wordt de display op 'none' gezet
    }, 6000);  // Verberg na 6 seconden

    } else {
        // Voor andere hover-messages (niet agenda)
        // Maak een nieuw hover-message element aan als het nog niet bestaat
        hoverMessageElement = element.querySelector(`.${className}`);
        if (!hoverMessageElement) {
            hoverMessageElement = document.createElement('div');
            hoverMessageElement.className = className;
            document.body.appendChild(hoverMessageElement);
        }

        // Stel de inhoud en stijl in (CSS verzorgt de styling verder)
        hoverMessageElement.textContent = message;
        hoverMessageElement.style.display = 'block';
        hoverMessageElement.style.opacity = '1';


        // Positionering voor niet-agenda hover-messages
        const rect = element.getBoundingClientRect();
        const scrollY = window.scrollY || window.pageYOffset;
        hoverMessageElement.style.left = `${rect.left + window.scrollX}px`;
        hoverMessageElement.style.top = `${rect.top + scrollY - hoverMessageElement.offsetHeight + 15}px`;
    
    }
    
}

function hideHoverMessage7() {
    const hoverMessageElement = document.querySelector('.hover-message');
    const hoverMessageElement_agenda = document.querySelector('.hover-message-agenda');

    if (hoverMessageElement) {
        hoverMessageElement.style.opacity = '0';

        setTimeout(() => {
            hoverMessageElement.style.display = 'none';
            hoverMessageElement.remove(); // Verwijder het element uit de DOM
        }, 300);
    }

    if (hoverMessageElement_agenda) {
        hoverMessageElement_agenda.style.opacity = '0';

        setTimeout(() => {
            hoverMessageElement_agenda.style.display = 'none';
            hoverMessageElement_agenda.remove(); // Verwijder agenda hover bericht uit de DOM
        }, 400);
    }
}

//--------Functions to display a menu on the form

const displayNiveauOptions = (schooltypeValue) => {
    if (schooltypeValue) {
        const validSchooltypeValues = Object.keys(leeftijden);
        if (validSchooltypeValues.includes(schooltypeValue)) {
            // Leeg de opties
            niveauOptions.innerHTML = '';

            // Voeg nieuwe opties toe op basis van schooltype
            Object.keys(leeftijden[schooltypeValue]).forEach((sleutel, index) => {
                const optie1 = document.createElement('option');
                if (sleutel === "PraktijkOnderwijs"){
                optie1.value = sleutel;
                optie1.textContent = 'Praktijk Onderwijs';
                niveauOptions.appendChild(optie1);
                }  else if (sleutel === "VMBO_BB_KB") {
                    optie1.value = sleutel;
                    optie1.textContent = "VMBO Basis Kader";
                    niveauOptions.appendChild(optie1);
                } else if (sleutel === "VMBO_GL_TL") {
                    optie1.value = sleutel;
                    optie1.textContent = "VMBO Gemengd Theoretisch";
                    niveauOptions.appendChild(optie1);
                } else {
                    optie1.value = sleutel;
                    optie1.textContent = sleutel;
                    niveauOptions.appendChild(optie1);
                }

                if (index === 0) {
                    optie1.selected = true;
                }
            });

            // Controleer of het PO of VO is
            if (schooltypeValue === "primairOnderwijs") {
                niveauOptions.multiple = false; // Eén selectie toegestaan
                niveauOptions.size = 2; // Compacte weergave
                updateNiveauLabel("primair"); // Update label
            } else {
                niveauOptions.multiple = true; // Meerdere selecties toegestaan
                niveauOptions.size = 5; // Ruimere weergave voor meerdere keuzes
                updateNiveauLabel("voortgezet");
            }
        }

        // Toon het niveau-selectieblok
        niveauDiv.style.display = 'block';
    }
};

const displayNiveauLeeftijdsGroepenOptionsPO = (schooltypeValue, niveauValues) => {
    leeftijdDivVO.innerHTML = "";
    leeftijdDivVO.style.display = "none";
   const schoolType = getGekozenOnderwijsNiveau();
   let isValidPONiveau;
   if (schoolType === "basis"){ 
    if (schooltypeValue && niveauValues) {
        const validPONiveaus = Array.from(Object.keys(leeftijden[schooltypeValue]))
        isValidPONiveau = validPONiveaus.includes(niveauValues);
        if (isValidPONiveau) {
            leeftijdOptionsPO.innerHTML = "";
            leeftijden[schooltypeValue][niveauValues].forEach(
                (leeftijd, index) => {
                    const optie1 = document.createElement('option');
                    optie1.value = leeftijd;
                    optie1.textContent = leeftijd;
                    leeftijdOptionsPO.appendChild(optie1);

                    if (index === 0) {
                        optie1.selected = true;
                    }

                }
            )
            leeftijdOptionsPO.multiple = true;
            leeftijdOptionsPO.size =  leeftijden[schooltypeValue][niveauValues].length
            leeftijdDivPO.style.display = 'block';

        }

    } else {
        leeftijdDivPO.style.display = "none";
    }

   } else {
        leeftijdDivPO.style.display = "none";
   }
};

const displayNiveauLeeftijdsGroepenOptionsVO = (schooltypeValue, niveauValues) => {
    leeftijdDivPO.style.display = "none";
    leeftijdDivVO.innerHTML = ""; // Maak container leeg bij elke update


    if (schooltypeValue === "voortgezetOnderbouw" || schooltypeValue === "voortgezetBovenbouw") {
        if (niveauValues && niveauValues.length > 0) {
            leeftijdDivVO.classList.add("leeftijd-grid");
     
            // Voeg een select-veld toe voor elk gekozen niveau
            niveauValues.forEach((niveau, index) => {
                const gridColumn = document.createElement('div');
                gridColumn.style.gridColumn = index + 1;
                gridColumn.id = `leeftijdDivVOGridColumn_${index + 1}`;
                gridColumn.className = "grid-column";
                gridColumn.style.width = "1fr";
                const leeftijdsGroepen = leeftijden[schooltypeValue][niveau]; // Haal leeftijdsgroepen op
                if (Array.isArray(leeftijdsGroepen) && leeftijdsGroepen.length > 0) {
                    const select = document.createElement("select");
                    const selectFout = document.createElement('div');
                    selectFout.className = "foute-invoermelding";
                    select.name = `leeftijdsgroepen_${niveau}[]`;
                    select.niveau = niveau;
                    select.multiple = true;
                    select.size = leeftijdsGroepen.length;
                    select.id = `onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}`;
                    selectFout.id = `onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}Fout`
                    select.className = "multipleSelectNiveau";
                    select.style.gridRow = 2;
                    select.classList.add("customBorderStyle");
                    select.addEventListener('mousedown', function (e) {
                        e.preventDefault(); // Voorkom standaard dropdown gedrag
                        const selected = e.target;
                    
                        if (selected.tagName === 'OPTION') {
                            selected.selected = !selected.selected; // Toggle de selectie
                        }

                        const geselecteerdeOpties = Array.from(select.selectedOptions); // Array van geselecteerde opties
                        
                        if (geselecteerdeOpties.length > 3) {
                            // Deselecteer de laatst geselecteerde optie
                            geselecteerdeOpties[geselecteerdeOpties.length - 1].selected = false;
                    
                        
                        }

                        
                        const event = new Event('change');
                        select.dispatchEvent(event); // Roep het blur-event aan
                      
                        
                    });

                    select.addEventListener('change', () => {
                        valideerInvoer(select, selectFout, valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenVO)
                       
                    })

           
                    


              

                    const label = document.createElement("label");
                    label.htmlFor = select.id;
                    label.style.display = "block";
                    label.style.minHeight = `calc(${niveauValues.length} * 1.4em)`;
                    label.style.lineHeight = "1.4em"; // Consistente regelhoogte
                    label.className = "label-styling";
                    label.id = `onderwijsSchooltypeNiveauLeeftijdsGroepenLabelVO_${niveau}`;
                    let spanClass = "";
                    niveauValues.length === 1 ? spanClass = "label-styling-nowrap-span inline-style" : spanClass = "label-styling-nowrap-span"
                    if (niveau === "VMBO_BB_KB") {
                    label.innerHTML = `
                        <span class= "${spanClass}" data-info-tip="klik links om de groep te (de)selecteren">
                            Selecteer</span> 
                          de ${"VMBO Basis Kader"} groep(en)
                    `;} else if (niveau === "VMBO_GL_TL") {
                        label.innerHTML = `
                        <span class= "${spanClass}" data-info-tip="klik links om de groep te (de)selecteren">
                            Selecteer</span> 
                          de ${"VMBO Gemengde Theoretische"} groep(en)
                    `;}
                    else {
                        label.innerHTML = `
                        <span class= "${spanClass}" data-info-tip="klik links om de groep te (de)selecteren">
                            Selecteer</span> 
                          de ${niveau} groep(en)
                    `;
                    }

                
                    // Voeg opties toe aan het select-veld
                    leeftijdsGroepen.forEach((leeftijd, index) => {
                        const optie = document.createElement("option");
                        optie.value = leeftijd;
                        optie.textContent = leeftijd;
                        if (index === 0){
                            optie.selected = true;
                        }
                        select.append(optie);
                    });

    
                

                    // Voeg het label en select-veld toe aan de container
                    gridColumn.append(label);
                    gridColumn.append(select);
                    gridColumn.append(selectFout);
                    
                    
                }
                leeftijdDivVO.append(gridColumn);
              
            });
            
            
            leeftijdDivVO.style.display = "grid";
           
        } else {
            leeftijdDivVO.style.display = "none"; // Verberg container als er geen niveaus zijn
        }
    } else {
        leeftijdDivVO.style.display = "none"; // Verberg container voor niet-VO
    }
};


function initFlatpickr(agendaData) {
    const currentDate = new Date();
    const endOfYearDate = new Date(currentDate.getFullYear()+1, 11, 31);  // 31 december van dit jaar

    // Array met schoolvakanties
    const schoolVacations = [
        { start: '2025-01-01', end: '2025-01-05'},
        { start: '2025-02-15', end: '2025-03-09' },  // Voorjaarsvakantie 2025
        { start: '2025-04-18', end: '2025-05-04' },  // Meivakantie 2025
        { start: '2025-05-29', end: '2025-06-01' },  // Meivakantie 2025
        { start: '2025-06-07', end: '2025-06-09' },  // Meivakantie 2025
        { start: '2025-07-05', end: '2025-08-31' },  // Zomervakantie 2025
        { start: '2025-10-12', end: '2025-10-26' },  // Herfstvakantie 2025
        { start: '2025-12-21', end: '2025-12-31' }   // Kerstvakantie 2025
    ];
    

    // Functie om te checken of een datum in de vakantieperiode valt
    function isVacation(date) {
        return schoolVacations.some(vacation => {
            const start = new Date(vacation.start);
            const end = new Date(vacation.end);
            return date >= start && date <= end;
        });
    }

    const disabledDates = [];
    flatpickr.localize(flatpickr.l10ns.nl);

    flatpickr("#bezoekdatum", {
        locale: "nl",  // Locale Nederlands instellen
        enableTime: false,
        dateFormat: "d F Y",  // Dag, maand, jaar
        disable: [
            function (date) {
                const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
                const isSchoolVacation = isVacation(date);
                const isPastDate = date < currentDate;  // Datum uit het verleden uitschakelen
                const isNextYear = date > endOfYearDate;  // Datum na dit jaar uitschakelen
    
                const isDisabled = isWeekend || isSchoolVacation || isPastDate || isNextYear;
    
                if (isDisabled) {
                    disabledDates.push(date.setHours(0, 0, 0, 0));  // Voeg disabled datums toe
                }
    
                return isDisabled;
            }
        ],
        appendTo: document.body,
    
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            const dayTimestamp = dayElem.dateObj.setHours(0, 0, 0, 0); // Normale timestamp zonder tijd
            
            resetDayStyles(dayElem);
    
            // Zoek naar een match met agendaData op basis van de datum
            const dateMatch = agendaData.find(item => new Date(item.bezoekdatum).setHours(0, 0, 0, 0) === dayTimestamp);
            
            if (dateMatch) {
                if (dateMatch.status === 'volgeboekt') {
                    applyStyling(dayElem, 'red', 'white', 'Volgeboekt', '#081540');
                    dayElem.classList.add('flatpickr-disabled');
                } else // Beperkt beschikbaar - met geel en groen gradient
                if (dateMatch.status === 'beperkt beschikbaar') {
                    const beschikbarePlaatsen = 160 - parseInt(dateMatch.totale_leerlingen, 10);
                    applyStyling(
                        dayElem, 
                        '#32CD32',  // Lichte groene tint naar een donkerder groen
                        'white',  // Tekstkleur
                        `Beperkt te boeken voor ${beschikbarePlaatsen} leerlingen`, 
                        'white'  // Gouden rand voor een subtiele waarschuwing
                    );
                }              
            } else {
                const isFutureDate = dayElem.dateObj >= currentDate && dayElem.dateObj <= endOfYearDate;
                if (isFutureDate && !disabledDates.includes(dayTimestamp)) {
                    applyStyling(dayElem, 'green', 'white', 'Te boeken voor max 160 leerlingen', 'white');
                }
            }
    
            if (!dayElem.classList.contains('flatpickr-disabled')) {
                // Hover effect
                dayElem.addEventListener('mouseenter', () => {
                    dayElem.style.transition = 'box-shadow 0.3s ease, transform 0.3s ease';
                    dayElem.style.boxShadow = '0 6px 12px rgba(0, 0, 0, 0.4)';
                    dayElem.style.borderRadius = '30px'; // Maak de hoeken ronder bij hover
                });
    
                dayElem.addEventListener('mouseleave', () => {
                    dayElem.style.boxShadow = 'none';
                    dayElem.style.transform = 'scale(1)';
                    dayElem.style.borderRadius = '30px'; // Herstel de cirkelvorm
                });
    
            }
            if (dayElem.classList.contains('selected')) {   
                dayElem.style.fontWeight = 'bold';
                dayElem.style.color = 'white';
                dayElem.style.border = '2px solid white';
                dayElem.style.background = 'linear-gradient(135deg, rgba(255, 215, 0, 0.7), rgba(8, 21, 64, 0.7))'; // Goud en donkerblauw
                dayElem.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.4)'; // Iets donkerdere schaduw               
            }
        }
    });
    
}


// Herstel dag stijlen
function resetDayStyles(dayElem) {
    dayElem.style.backgroundColor = '';  
    dayElem.style.color = '';            
    dayElem.title = '';                  
}

// Pas stijlen toe op de dagen
function applyStyling(dayElem, backgroundColor, textColor, title, border = null, className = 'hover-message-agenda') {
    dayElem.style.backgroundColor = backgroundColor;
    dayElem.style.color = textColor;
    dayElem.title = "";  // Leegmaken van het standaard title-attribuut

    // Voeg een rand toe indien border is meegegeven
    if (border) {
        dayElem.style.border = `3px solid ${border}`;
        dayElem.style.borderRadius = '30px';
    }

    // Voeg de hover event listeners toe
    dayElem.addEventListener('mouseenter', () => {
            
        scheduleHoverMessage4(title, dayElem, className);  // Hoverfunctie voor de tooltip
    });   
    
}

//----------||[Validation Funcs]||---------------------/

//inline

function valideerInvoer(veld, foutElement, validatieFunctie) {
    let waarde;

    // Als de validatiefunctie 'verzendknopFoutMelding' is, haal de waarde uit het foutElement in plaats van het veld
    if (validatieFunctie.name === "verzendknopFoutMelding") {
        waarde = foutElement.textContent.trim(); // Haal de waarde uit het foutElement
    } else if (validatieFunctie.name === "valideerOnderwijsSchooltypeNiveau") {
        waarde = getNiveauValues();
    } else if (validatieFunctie.name === "valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenPO") {
        waarde = getLeeftijdsgroepenPOValues();
    } else if (validatieFunctie.name === "valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenVO") {
        waarde = {
            niveau : veld.niveau,
            geselecteerdeOpties: Array.from(veld.selectedOptions).map(option => option.value)
        }
    }
    else {
        waarde = veld.value.trim(); // Haal de waarde uit het invoerveld zoals gewoonlijk
    }
    

    let foutmelding = validatieFunctie(waarde); // Roep de specifieke validatiefunctie aan

    
    if (foutmelding) {
        if (validatieFunctie.name === "verzendknopFoutMelding") {
            veld.focus();

            setTimeout(() => {
                toonFoutmelding(foutElement, foutmelding, veld);
            },600);
         } else if (validatieFunctie.name === "valideerOnderwijsSchooltypeNiveau" || validatieFunctie.name === "valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenPO" || validatieFunctie.name === "valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenVO") {
            toonFoutmelding(foutElement, foutmelding, veld, 10000);
         }
          else{
            toonFoutmelding(foutElement, foutmelding, veld);
        }
        
        
        // Speciale weergave voor specifieke foutmeldingen
        if (foutmelding === "Dit veld mag niet leeg blijven.") {
            veld.classList.remove('foute-invoermelding__errorField', 'extraLayer');
        } else if (foutmelding === "Voer een geldig getal in tussen 0 en 200.") {
            veld.classList.remove('foute-invoermelding__errorField', 'extraLayer');      
        } else if (foutmelding === "Aanvraag succesvol ontvangen!") {
            applyCommonStyles(foutElement, '#1c2541');  // Donkerblauw
        } else if (foutmelding === "Er is een fout opgetreden bij het verwerken van de aanvraag!") {
            applyCommonStyles(foutElement, '#ff0000');  // Rood voor foutmeldingen
        } 
        else {
            veld.classList.add('foute-invoermelding__errorField', 'extraLayer');
        }
        return false; // Keer terug als er een foutmelding is
    } else {
        hideFoutmelding(foutElement); // Verberg de foutmelding als er geen is 
        veld.classList.remove('foute-invoermelding__errorField', 'extraLayer');
        return true; // Geen foutmelding
    }
}


function valideerVoornaam(waarde) {
    const maxLength = 50;
    const onlyLetters = /^[\p{L}\s.-]*$/u;

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
    
    if (waarde.length > maxLength) {
        return `De voornaam mag maximaal ${maxLength} tekens bevatten.`;
    } else if (!onlyLetters.test(waarde)) {
        return "De voornaam mag alleen letters bevatten.";
    }
    return ""; // Geen foutmelding
}

function valideerAchternaam(waarde) {
    const maxLength = 50;
    const onlyLetters = /^[\p{L}\s.-]*$/u;

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
    

    if (waarde.length > maxLength) {
        return `De achternaam mag maximaal ${maxLength} tekens bevatten.`;
    } else if (!onlyLetters.test(waarde)) {
        return "De achternaam mag alleen letters en spaties bevatten.";
    }
    return ""; // Geen foutmelding
}

function valideerEmail(waarde) {
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
    const maxLength = 100; // Maximale lengte van het e-mailadres

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
    

    if (waarde.length > maxLength) {
        return `Het e-mailadres mag maximaal ${maxLength} tekens bevatten.`;
    } else if (!emailRegex.test(waarde)) {
        return "Voer een geldig e-mailadres in.";
    }

    return ""; // Geen foutmelding
}

function valideerTelefoonnummer(waarde) {
    const telefoonRegex = /^(\+31|0)[1-9][0-9]{8}$|^(\+31|0)[1-9][0-9]{1,3}-[0-9]{6,7}$/;
    const maxLength = 15; // Maximale lengte voor het telefoonnummer

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
        
    // Controleer de maximale lengte
    if (waarde.length > maxLength) {
        return `Het telefoonnummer mag maximaal ${maxLength} tekens bevatten.`;
    }

    // Valideer het telefoonnummer met de regex
    if (!telefoonRegex.test(waarde)) {
        return "Voer een geldig Nederlands telefoonnummer in.";
    }

    return ""; // Geen foutmelding
}

function valideerNiveauEnLeerjaar(waarde) {
    const maxLength = 50;
    const niveauRegex = /^[A-Za-z\s',]+\s[0-9]{1,2}$/;

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
    
    // Controleer de maximale lengte
    if (waarde.length > maxLength) {
        return `In dit veld mag je maximaal ${maxLength} tekens gebruiken.`;
    }

    // Valideer het telefoonnummer met de regex
    if (!niveauRegex.test(waarde)) {
        return "Het niveau en leerjaar worden correct omschreven als volgt: groep 6 of vmbo 3";
    }

    return ""; // Geen foutmelding

}

// Functie om naam van school te valideren
function valideerNaamSchool(waarde) {
    const naamSchoolRegex = /^[A-Za-z0-9\s.]+$/;
    const maxLength = 80; // Laten we een limiet instellen voor de lengte van de naam van de school

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven."; 
    }
    
    
    // Controleer of de naam te lang is
    if (waarde.length > maxLength) {
        return `De naam van de school mag maximaal ${maxLength} tekens bevatten.`;
    }

    // Controleer of de naam alleen toegestane karakters bevat
    if (!naamSchoolRegex.test(waarde)) {
        return "De naam van de school mag alleen letters, cijfers, spaties en punten bevatten.";
    }

    return ""; // Geen foutmelding
}

function valideerAdres(waarde) {
    const maxLength = 100;  // Adressen kunnen wat langer zijn
    const adresRegex = /^[A-Za-z0-9\s.,'-]+$/;

        if (waarde.length === 0) {
            return "Dit veld mag niet leeg blijven."; 
        }
        
    if (waarde.length > maxLength) {
        return `Het adres mag maximaal ${maxLength} tekens bevatten.`;
    } else if (!adresRegex.test(waarde)) {
        return "Het adres mag alleen letters, cijfers, spaties en de volgende tekens bevatten: ., '-";
    }
    return ""; // Geen foutmelding
}

function valideerPostcode(waarde) {
    const postcodeRegex = /^[1-9][0-9]{3}\s?[A-Za-z]{2}$/; // Nederlandse postcode-formaat

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven.";
    }

    if (!postcodeRegex.test(waarde)) {
        return "De ingevoerde postcode is niet geldig. Het formaat moet 1234 AB zijn.";
    }

    return ""; // Geen foutmelding
}

function valideerPlaats(waarde) {
    const maxLengthPlaats = 100;
    const plaatsRegex = /^[A-Za-z\s'-]+$/; // Alleen letters, spaties, apostrof en streepjes toegestaan

    if (waarde.length === 0) {
        return "Dit veld mag niet leeg blijven.";
    }

    if (waarde.length > maxLengthPlaats) {
        return `De plaatsnaam mag maximaal ${maxLengthPlaats} tekens bevatten.`;
    }

    if (!plaatsRegex.test(waarde)) {
        return "De plaatsnaam mag alleen letters, spaties, apostrof en streepjes bevatten.";
    }

    return ""; // Geen foutmelding
}

function valideerHoeKentGeoFort(waarde) {
    const regex = /^[A-Za-z0-9\s.]*$/; // Letters, cijfers, spaties, en punten toegestaan

    // Als het veld leeg is, geen foutmelding geven
    if (waarde.length === 0) {
        return ""; // Geen foutmelding, want het veld mag leeg zijn
    }

    // Controleer of de invoer voldoet aan de regex
    if (!regex.test(waarde)) {
        return "Dit veld mag alleen letters, cijfers, spaties en punten bevatten.";
    }

    return ""; // Geen foutmelding
}

function valideerOnderwijsNiveau(waarde) {
    const geldigeNiveaus = Object.keys(onderwijsModules); // Verkrijg de geldige onderwijsniveaus uit de modules
    if (!waarde) {
        return "Selecteer een onderwijsniveau."; // Geen waarde ingevuld
    }
    if (!geldigeNiveaus.includes(waarde)) {
        return "Ongeldig onderwijsniveau geselecteerd."; // Niet in de lijst met geldige niveaus
    }
    return ""; // Geen foutmelding
}

function valideerOnderwijsSchooltypeNiveau(waarde) {
    const schoolType = OnderwijsniveauSelect.value; // Haal het type onderwijs op
    const geldigeNiveaus = Object.keys(leeftijden[schoolType]); // Verkrijg geldige niveaus
    if (Array.isArray(waarde)) {
        // Meerdere selecties (voortgezet onderwijs)
        if (waarde.length === 0) {
            return "Selecteer ten minste één onderwijsniveau.";
        }
        if (waarde.length > 3) {
            return "Er kan tot max 3 niveaus geselecteerd worden";
        }
        const ongeldigeNiveaus = waarde.filter(selectie => !geldigeNiveaus.includes(selectie));
        if (ongeldigeNiveaus.length > 0) {
            return `Ongeldig onderwijsniveau geselecteerd: ${ongeldigeNiveaus.join(", ")}`;
        }
    } else {
        // Enkele selectie (primair onderwijs)
        if (!waarde || waarde.trim() === "") {
            return "Selecteer een onderwijsniveau.";
        }
        if (!geldigeNiveaus.includes(waarde)) {
            return "Ongeldig onderwijsniveau geselecteerd.";
        }
    }

    return ""; // Geen foutmelding
}

function valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenPO(waarde) {
    const schoolType = OnderwijsniveauSelect.value; // Haal het type onderwijs op
    if (schoolType && schoolType ==="primairOnderwijs") {
        const gekozenNiveauPO = getNiveauValues();
        const geldigeNiveausPO = Object.keys(leeftijden[schoolType]);
        if (geldigeNiveausPO && geldigeNiveausPO.includes(gekozenNiveauPO)) {
            if (Array.isArray(waarde) && waarde.length > 0) {                
            const geldigeLeeftijdsGroepen = leeftijden[schoolType][gekozenNiveauPO];
            const ongeldigeLeeftijdsGroepen = waarde.filter(selectie => !geldigeLeeftijdsGroepen.includes(selectie));
            if (ongeldigeLeeftijdsGroepen && ongeldigeLeeftijdsGroepen.length>0) {
                return "Ongeldige leeftijdsgroepen geselecteerd"
            }          
        } else if (!waarde.length) {
            return "Selecteer tenminste 1 leeftijdsgroep"
        }

    } 
}
}

function valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenVO(waarde) {
    if (waarde === null || typeof(waarde) !== "object" || typeof(waarde.niveau) !== "string" || !Array.isArray(waarde.geselecteerdeOpties)) {
        return "ongeldige data voor validatie"
    }

    const schoolType = OnderwijsniveauSelect.value; 
    if (schoolType === "voortgezetOnderbouw" || schoolType === "voortgezetBovenbouw") {
        let gekozenNiveaus = getNiveauValues();
        if (gekozenNiveaus.length === 0){
            return "Selecteer tenminste 1 Niveau"
        } if (!gekozenNiveaus.includes(waarde.niveau) || !Object.keys(leeftijden[schoolType]).includes(waarde.niveau)) {
            return "Ongeldige selectie gemaakt"
        } 
    
        const valideLeeftijdsgroepen = leeftijden[schoolType][waarde.niveau];

        if (waarde.geselecteerdeOpties.length === 0) {
            return "Selecteer ten minste 1 leeftijdsgroep"
        } else if (waarde.geselecteerdeOpties.length > 3) {
            return "Er mogen max 3 leeftijdsgroepen gekozen worden"
        } 

        const invalideKeuzes = waarde.geselecteerdeOpties.filter(selectie => !valideLeeftijdsgroepen.includes(selectie));

        if (invalideKeuzes.length > 0) {
            return "Ongeldige selectie gemaakt"
        }

        return ""

    } else {
        return "Ongeldig schoolType";
    }

}



// Stap 3: Functie voor validatie van de keuze module
function valideerKeuzeModule(waarde) {

    // Verzamel alle keuze modules uit het onderwijsModules object
    const alleKeuzeModules = [
        ...onderwijsModules.primairOnderwijs.keuze,
        ...onderwijsModules.voortgezetOnderbouw.keuze,
        ...onderwijsModules.voortgezetBovenbouw.keuze
    ];

    /**spread operator pakt alle waardes die hier staan en zet ze in 1 lijst */

    // Stap 5: Gebruik `some()` om te controleren of de gekozen module geldig is
    const isGeldig = alleKeuzeModules.some(module => module === waarde);

    if (!waarde){
        return "Selecteer een keuze-module.";
    } else if (!isGeldig){
        return "Maak een keuze uit de lijst.";
    } else {
        return "";
    }
}


function valideerAantalLeerlingen(waarde) {
    // Controleer of de invoer leeg is
    if (waarde === "") {
        return "Het aantal leerlingen moet worden doorgegeven."; // Als het veld leeg is
    }

    // Controleer of de invoer alleen cijfers bevat
    const cijferRegex = /^[0-9]+$/;
    if (!cijferRegex.test(waarde)) {
        return "Voer een geldig getal in."; // Als de invoer geen geldig nummer is
    }

    // Converteer de waarde naar een getal en valideer het bereik
    const aantal = parseInt(waarde, 10);
    if (aantal < 40 || aantal > 160) {
        return "Het aantal leerlingen moet tussen 40 en 160 liggen.";
    }

    return ""; // Geen foutmelding
}

function valideerAantalBegeleiders(waarde) {
    const aantalLeerlingen = aantalLeerlingenInput.value;
    const minAantalBegeleiders = Math.max(1, Math.ceil(aantalLeerlingen / 16));

    /**minimaal 1 en dan afronden naar boven met math.ceil */

    // Controleer of de invoer een geldig getal is tussen 1 en 50.
    const aantal = parseInt(waarde, 10);
    if (isNaN(aantal) || aantal < minAantalBegeleiders || aantal > 50 ) {
        return `Voer minimaal ${minAantalBegeleiders} begeleiders in en maximaal 50.`;
    }

    return ""; // Geen foutmelding
}

function valideerInput(amountInput) {
    const cijferRegex = /^[0-9]+$/; // Regex om alleen cijfers toe te staan
    let waarde = amountInput.value.trim();

    const amountInputid = amountInput.id
    let defaultValue;

    amountInputid === "remiseLunchAantal" ? defaultValue = 50 : defaultValue = 0;

    // Als de invoer leeg is, zet de waarde automatisch op 0 en geen foutmelding
    if (waarde === '') {
        amountInput.value = defaultValue;
        return ""; // Geen foutmelding
    }

    // Trim leidende nullen (behalve als de waarde gewoon '0' is)
    if (waarde.startsWith('0') && waarde.length > 1) {
        waarde = waarde.replace(/^0+/, ''); // Verwijder leidende nullen
        amountInput.value = waarde; // Update het invoerveld
    }

    // Controleer of de invoer alleen cijfers bevat
    if (!cijferRegex.test(waarde)) {
        amountInput.value = defaultValue; // Zet de waarde naar 0 als de invoer ongeldig is
        return "Voer een geldig getal in tussen 0 en 200."; // Ongeldige invoer
    }

    const aantal = parseInt(waarde, 10);

    if (defaultValue === 0) {
        if (aantal < 0 || aantal > 200) {
            amountInput.value = defaultValue; // Zet de waarde naar 0 als het buiten het bereik valt
            return `Voer een geldig getal in tussen ${defaultValue} en 200.";` // Ongeldig bereik
        }
    } else {
        if (aantal < 50 || aantal > 200) {
            amountInput.value = defaultValue; // Zet de waarde naar 0 als het buiten het bereik valt
            return `Voer een geldig getal in tussen ${defaultValue} en 200.`; // Ongeldig bereik
        }

    }


    return ""; // Geen foutmelding
}

function valideerVragenenOpmerkingen(waarde) {
    const maxLength = 600;  
    const vragenenOpmerkingenRegex = /^[A-Za-z0-9\s.,:?!]+$/;

    if (waarde.length === 0){
        return "";
    }

    if (waarde.length > maxLength) {
        return `Dit veld mag max ${maxLength} tekens bevatten.`;
    } else if (!vragenenOpmerkingenRegex.test(waarde)) {
        return "Speciale tekens zoals } of / kunnen niet gebruikt worden";
    }
    return ""; // Geen foutmelding
    }

    function verzendknopMelding(waarde) {
    return waarde;
    }

    function verzendknopFoutMelding(waarde) {
    return waarde;
}


 

//met muisklik selecteren of deselecteren van niveaus

function valideerVelden(veld, veldElement, foutElement, aanvraagscriptVerzendData) {
    if (aanvraagscriptVerzendData.gekozenOnderwijsNiveau === "basis"){
        switch (veld) {
                case 'contactpersoonvoornaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'contactpersoonachternaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'emailadres':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'schoolTelefoonnummer':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'contactTelefoonnummer':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'totaalBegeleiders':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'schoolnaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'adres':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'postcode':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'plaats':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'onderwijsNiveau':
                    valideerInvoer(veldElement,foutElement, verzendknopFoutMelding);
                    break;
                case 'keuzeModule':
                    valideerInvoer(veldElement,foutElement, verzendknopFoutMelding);
                    break;
                case 'onderwijsSchooltypeNiveau':
                    valideerInvoer(niveauOptions, niveauFout, verzendknopFoutMelding);
                    break;
                case 'onderwijsSchooltypeNiveauLeeftijdsGroepenPO':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'aantalLeerlingen':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'bezoekdatum':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'remiseBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'kazerneBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'fortgrachtBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'waterijsjeAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'pakjeDrinkenAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'remiseLunchAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'eigenPicknickCheckbox':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'vragenOpmerkingen':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                default:
                    break;
            }
        } else if (aanvraagscriptVerzendData.gekozenOnderwijsNiveau === "voortgezet"){
            const leeftijdsGroepsVelden = [];
            aanvraagscriptVerzendData.gekozenNiveaus.forEach(niveau => {
            let leeftijdsGroepsVeld = `onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}`;
            leeftijdsGroepsVelden.push(leeftijdsGroepsVeld);
            })
            switch (veld) {
                case 'contactpersoonvoornaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'contactpersoonachternaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'emailadres':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'schoolTelefoonnummer':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'contactTelefoonnummer':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'totaalBegeleiders':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'schoolnaam':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'adres':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'postcode':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'plaats':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'onderwijsNiveau':
                    valideerInvoer(veldElement,foutElement, verzendknopFoutMelding);
                    break;
                case 'keuzeModule':
                    valideerInvoer(veldElement,foutElement, verzendknopFoutMelding);
                    break;
                case 'onderwijsSchooltypeNiveau':
                    valideerInvoer(niveauOptions, niveauFout, verzendknopFoutMelding);
                    break;
                case 'aantalLeerlingen':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'bezoekdatum':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'remiseBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'kazerneBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'fortgrachtBreakAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'waterijsjeAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'pakjeDrinkenAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'remiseLunchAantal':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'eigenPicknickCheckbox':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'vragenOpmerkingen':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                case 'prijs':
                    valideerInvoer(veldElement, foutElement, verzendknopFoutMelding);
                    break;
                default:
                    // Itereer door de leeftijdsgroepsvelden en valideer elk veld
                    for (let leeftijdsGroepsVeld of leeftijdsGroepsVelden){
                        if (veld === leeftijdsGroepsVeld) {
                            const customVeldelement = document.getElementById(`${leeftijdsGroepsVeld}`);
                            const foutElement = document.getElementById(`${leeftijdsGroepsVeld}Fout`); // Dynamisch ID
                            valideerInvoer(customVeldelement, foutElement, verzendknopFoutMelding);
                            break;
                        }
                    };
                    break;
                    
                
            }

        }
}

//Function to start up the form with default values
function initialiseerFormulier() {
    OnderwijsniveauSelect.value = 'primairOnderwijs'; // Stel het standaard onderwijsniveau in
    werkKeuzeModulesBij1();  // Vul de keuzemodules en standaardmodules in
    werkGeselecteerdeModulesWeergaveBij2();  // Update de weergave van modules
    updateMeerInformatieLink();
    displayNiveauOptions(OnderwijsniveauSelect.value);
    const selectedNiveaus = getNiveauValues()
    displayNiveauLeeftijdsGroepenOptionsPO(OnderwijsniveauSelect.value, selectedNiveaus);
}


//functie om het formulier te verzenden
const verzendFormulier = async (formData, aanvraagscriptVerzendData) => {
    const verzendknop = document.getElementById('verzendknop');
    const verzendMeldingdiv = document.getElementById("verzendknopMeldingdiv");

  

    const timeoutID = setTimeout(() => {
        verzendknop.textContent = "Het verwerken van de aanvraag is bezig en duurt langer dan verwacht...";
    }, 4000);

    try {
        const response = await fetch('validatie.php', {
            method: 'POST',
            body: formData
        });

        clearTimeout(timeoutID);
        verzendknop.textContent = "Verzenden";

        if (!response.ok) {
            throw new Error(`ServerFout: ${response.status}`);
        }

        // JSON-parsing
        const serverRespons = await response.json();

        if (serverRespons.success) {
            verzendknop.value = serverRespons.message; // Gebruik textContent i.p.v. value
            valideerInvoer(verzendknop, verzendMeldingdiv, verzendknopMelding);
        } else if (serverRespons.errors) {
            for (let veld in serverRespons.errors) {
                const veldElement = document.getElementById(veld);
                let foutElement = (veld === "onderwijsSchooltypeNiveau") ? niveauFout : document.getElementById(`${veld}Fout`);
                if (foutElement && veldElement) {
                    foutElement.textContent = serverRespons.errors[veld]; 
                    valideerVelden(veld, veldElement, foutElement, aanvraagscriptVerzendData);
                }
            }
        } else if (serverRespons.servererror) {
            throw new Error(serverRespons.servererror);
        }

    } catch (error) {
        verzendMeldingdiv.textContent = "Er is een fout opgetreden bij het verwerken van de aanvraag!";
         valideerInvoer(verzendknop, verzendMeldingdiv, verzendknopFoutMelding);
    } finally{
        verzendknop.textContent = "verzenden";
    }
}

//-----------------------[functies inzetten]----------------------------------//
// Roep de initialisatiefunctie aan bij het laden van de pagina
initialiseerFormulier();

// Voor de zekerheid wordt de teller bij het laden van de pagina bijgewerkt
updateTekenTeller();

//agenda initialiseren
initFlatpickr(calenderDataFromPHP);

//Food summry op 0 stellen bij op start
updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput);


//**||--------------[validatie van de velden]---------------------------||*/
Form.addEventListener('focusout', function(event) {
    const veld = event.target;
    if (!veld || !veld.id) return; // Veiligheidscheck

    const Foutveld = document.getElementById(`${veld.id}Fout`);
    if (!Foutveld) return;

    const validatieFuncties = {
        'contactpersoonvoornaam': valideerVoornaam,
        'contactpersoonachternaam': valideerAchternaam,
        'emailadres': valideerEmail,
        'schoolTelefoonnummer': valideerTelefoonnummer,
        'contactTelefoonnummer': valideerTelefoonnummer,
        'schoolnaam': valideerNaamSchool,
        'postcode': valideerPostcode,
        'plaats': valideerPlaats,
        'adres': valideerAdres,
        'totaalBegeleiders':valideerAantalBegeleiders,
        'keuzeModule': valideerKeuzeModule,
        'hoekentGeoFort': valideerHoeKentGeoFort,
        'vragenOpmerkingen': valideerVragenenOpmerkingen
    };

    if (veld.id === 'aantalLeerlingen') {
        valideerAantalLeerlingenInvoer(); 
        return; // **Hiermee stopt de functie direct!**
    } else if (veld.id === 'totaalBegeleiders'){
        valideerAantalBegeleidersInvoer();
        return;
    }

    if (validatieFuncties[veld.id]) {
        valideerInvoer(veld, Foutveld, validatieFuncties[veld.id]);  
    }
});

aantalBegeleidersInput.addEventListener('click', () => {
    console.log(aantalBegeleidersInput.value);
    if (aantalLeerlingenInput.value === "" || aantalLeerlingenInput.value < 40 || aantalLeerlingenInput.value > 160) {
        aantalBegeleidersInput.disable = true;
        aantalLeerlingenInput.focus();
        valideerAantalLeerlingenInvoer();
      
    }
})

niveauOptions.addEventListener('change', function() {
 // Valideer invoer met de aangepaste valideerInvoer-functie
    valideerInvoer(niveauOptions, niveauFout, valideerOnderwijsSchooltypeNiveau);
});

// Luister naar het invoerevenement om de teller te updaten
textarea.addEventListener('input', updateTekenTeller);

OnderwijsniveauSelect.addEventListener("change", () => {
    const targetContent = document.getElementById("onderwijsNiveauInfo");

    // Controleer of de content open is en sluit deze
    if (targetContent.classList.contains("open")) {
        targetContent.classList.remove("open");
    }
    updateMeerInformatieLink();
    displayNiveauOptions(OnderwijsniveauSelect.value);
    werkKeuzeModulesBij1();
    haalRoosterOp();
    calculateVisitPrice8(aantalBegeleidersInput, aantalLeerlingenInput);
    updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput);
    const schoolTypeValue = OnderwijsniveauSelect.value;
    const niveauValues = getNiveauValues();
    const schoolType = getGekozenOnderwijsNiveau();
    if (schoolType === "basis") {
        displayNiveauLeeftijdsGroepenOptionsPO(schoolTypeValue, niveauValues);
    } else if (schoolType === "voortgezet") {
        displayNiveauLeeftijdsGroepenOptionsVO(schoolTypeValue, niveauValues);

    }
});



niveauDiv.addEventListener('mousedown', function (e) {
    e.preventDefault(); // Voorkom standaard dropdown gedrag
    const optie = e.target;



    if (optie.tagName === 'OPTION') {
        optie.selected = !optie.selected; // Toggle de selectie
       
    }

   
   

    const event = new Event('change');
    niveauDiv.dispatchEvent(event); // Roep het blur-event aan
    
});

leeftijdDivPO.addEventListener('mousedown', function (e) {
    e.preventDefault(); // Voorkom standaard dropdown gedrag
    const optie = e.target;

    if (optie.tagName === 'OPTION') {
        optie.selected = !optie.selected; // Toggle de selectie
    }
    const GekozenOnderwijsNiveau = getGekozenOnderwijsNiveau();
    if (GekozenOnderwijsNiveau === "basis") {
    valideerInvoer(leeftijdOptionsPO, leeftijdOptionsPOFout, valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenPO);  
    }
});


niveauDiv.addEventListener('change', function () {
    const geselecteerdeOpties = Array.from(niveauOptions.selectedOptions);
    if (geselecteerdeOpties.length > 3) {
        // Deselecteer de laatst geselecteerde optie
        
        geselecteerdeOpties[geselecteerdeOpties.length - 1].selected = false;}
    

    valideerInvoer(niveauOptions, niveauFout, valideerOnderwijsSchooltypeNiveau);

    const schoolType = getGekozenOnderwijsNiveau();
    const schoolTypeValue = OnderwijsniveauSelect.value;
    const niveauValues = getNiveauValues();

    if (schoolType === "basis") {
        displayNiveauLeeftijdsGroepenOptionsPO(schoolTypeValue, niveauValues);
    } else if (schoolType === "voortgezet") {
        displayNiveauLeeftijdsGroepenOptionsVO(schoolTypeValue, niveauValues);

    }

});


// Voeg event listener toe om geselecteerde modules bij te werken wanneer de keuzemodule verandert
keuzeModule.addEventListener('change', () => {
    werkGeselecteerdeModulesWeergaveBij2();
    werkGeselecteerdeModulesWeergaveBij2();
    haalRoosterOp();
});


aantalLeerlingenVeld.addEventListener('input', () => {
    haalRoosterOp();
    valideerAantalLeerlingenInvoer();
    updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput);
});


aantalBegeleidersInput.addEventListener('input', function() {
    updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput);
});


toggles.forEach(toggle => {
    toggle.addEventListener("click", function(event) {
        event.preventDefault(); // Voorkom dat de pagina scrollt naar de top
        
        // Haal het doel-element op via het data-target attribuut
        const targetId = toggle.getAttribute("data-target");
        const targetContent = document.getElementById(targetId);

        // Toggle de "open" class
        const isOpen = targetContent.classList.toggle("open");

        // Pas de tekst van de toggle-link aan
        if (isOpen) {
            toggle.querySelector("span").textContent = "Verberg ";
        } else {
            if (targetId === "onderwijsNiveauInfo") {
                updateMeerInformatieLink();
            } else if (targetId === "begeleidersInfo") {
                toggle.querySelector("span").textContent = "Meer informatie over aantal begeleiders";
            } else if (targetId === "telefoonInfo") {
                toggle.querySelector("span").textContent = "Meer informatie over telefoonnummers";
            } else if (targetId === "bezoektijdenInfo") {
                toggle.querySelector("span").textContent = "Meer informatie over bezoektijden";
            }  else if (targetId === "meerInformatieAantalLeerlingen") {
                toggle.querySelector("span").textContent = "Meer informatie over het aantal leerlingen";
            }
        }
    });
});


foodInputs.forEach(input => {
    const snackOption = input.closest('.snack-option');
    const amountInput = snackOption ? snackOption.querySelector('input[type="number"]') : null;

    if (amountInput) {
        // Start detectie van input-activiteit
        amountInput.addEventListener('input', function () {
            const foutElement = document.getElementById(`${amountInput.id}Fout`);

            // Valideer de invoer tijdens het typen
            valideerInvoer(amountInput, foutElement, () => {
                return valideerInput(amountInput); // Input validatie, deze syntax is een callback functie, die valideerinput pas inzet nadat de divs zijn ontvangen
            });
        });
    }
});

foodInputs.forEach(input => {
    input.addEventListener('change', function () {
        const amountInput = input.closest('.snack-option').querySelector('input[type="number"]');
        if (amountInput) {
            amountInput.disabled = !this.checked;
            if (!this.checked) {
                amountInput.id === "remiseLunchAantal" ? amountInput.value = 50 : amountInput.value = 0;
                amountInput.disabled = true;  // Maak het invoerveld disabled

                // Reset inline styling (verwijder fout-styling)
                amountInput.style.border = '';  // Reset de border
                amountInput.style.backgroundColor = '';  // Reset de achtergrondkleur
                
                // Optioneel: Voeg een class of inline-styling toe voor disabled status
                amountInput.classList.add('disabled');  // Voeg disabled-stijl toe
            }
        }
        updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput);
    });

    const amountInput = input.closest('.snack-option').querySelector('input[type="number"]');
    if (amountInput) {
        amountInput.addEventListener('input',  updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput));
    }
});

document.getElementById('onderwijsFormulier').addEventListener('submit', function (event) {

    event.preventDefault();  // Voorkom standaard formulierverzending
    let gekozenLeeftijdsGroepenPO;
    const aanvraagscriptVerzendData = {
        gekozenOnderwijsNiveau: "",
        gekozenNiveaus: [],
        gekozenLeeftijdsgroepenPO: []
    }



    // Definieer alle verplichte velden die niet leeg mogen zijn
    const verplichteVelden = [
        { veld: document.getElementById('schoolnaam'), foutElement: document.getElementById('schoolnaamFout'), validatieFunctie: valideerNaamSchool },
        { veld: document.getElementById('adres'), foutElement: document.getElementById('adresFout'), validatieFunctie: valideerAdres },
        { veld: document.getElementById('postcode'), foutElement: document.getElementById('postcodeFout'), validatieFunctie: valideerPostcode },
        { veld: document.getElementById('plaats'), foutElement: document.getElementById('plaatsFout'), validatieFunctie: valideerPlaats },
        { veld: document.getElementById('schoolTelefoonnummer'), foutElement: document.getElementById('schoolTelefoonnummerFout'), validatieFunctie: valideerTelefoonnummer },
        { veld: document.getElementById('contactTelefoonnummer'), foutElement: document.getElementById('contactTelefoonnummerFout'), validatieFunctie: valideerTelefoonnummer },
        { veld: document.getElementById('contactpersoonvoornaam'), foutElement: document.getElementById('contactpersoonvoornaamFout'), validatieFunctie: valideerVoornaam },
        { veld: document.getElementById('contactpersoonachternaam'), foutElement: document.getElementById('contactpersoonachternaamFout'), validatieFunctie: valideerAchternaam },
        { veld: document.getElementById('emailadres'), foutElement: document.getElementById('emailadresFout'), validatieFunctie: valideerEmail },
        { veld: document.getElementById('totaalBegeleiders'), foutElement: document.getElementById('aantalBegeleidersFout'), validatieFunctie: valideerAantalBegeleiders },
        { veld: document.getElementById('aantalLeerlingen'), foutElement: document.getElementById('aantalLeerlingenFout'), validatieFunctie: valideerAantalLeerlingen },
        { veld: OnderwijsniveauSelect, foutElement: document.getElementById('onderwijsNiveauFout'), validatieFunctie: valideerOnderwijsNiveau },
        { veld: niveauOptions, foutElement: niveauFout, validatieFunctie: valideerOnderwijsSchooltypeNiveau},
        { veld: document.getElementById('keuzeModule'), foutElement: document.getElementById('keuzeModuleFout'), validatieFunctie: valideerKeuzeModule }
    ];

    let isFormulierGeldig = true;

    // Valideer verplichte velden
    for (const veldInfo of verplichteVelden) {
        const isGeldig = valideerInvoer(veldInfo.veld, veldInfo.foutElement, veldInfo.validatieFunctie);

        // Als er een fout is, focus op het eerste veld dat niet geldig is en stop met de controle
        if (!isGeldig) {
            veldInfo.veld.focus();  // Verplaats de focus naar het foutieve veld
            isFormulierGeldig = false;
            break;  // Stop met het valideren van andere velden
        }
    }

    // Stop de verdere verwerking als het formulier niet geldig is
    if (!isFormulierGeldig) {
        return false;
    }
    // Voeg de validatie toe voor de datum met de flatpickr
    const datumVeld = document.getElementById('bezoekdatum');
    const datumFoutElement = document.getElementById('bezoekdatumFout');
    const datumPicker = datumVeld._flatpickr;
    const geselecteerdeDatum = datumPicker.selectedDates[0];

    if (!geselecteerdeDatum || isNaN(geselecteerdeDatum)) {
        toonFoutmelding(datumFoutElement, "Selecteer een geldige datum.", datumVeld);
        datumVeld.focus();
        isFormulierGeldig = false;
    } else {
        const isDisabledDatum = datumPicker.config.disable.some(disableRule => {
            if (typeof disableRule === 'function') {
                return disableRule(geselecteerdeDatum);
            }
            if (disableRule instanceof Date) {
                return disableRule.getTime() === geselecteerdeDatum.getTime();
            }
            return false;
        });
        if (isDisabledDatum) {
            datumVeld.focus();
            isFormulierGeldig = false;
        } else {
            hideFoutmelding(datumFoutElement);
        }
    }

    // Valideer optionele velden
    const optioneleVelden = [
        { veld: document.getElementById('hoekentGeoFort'), foutElement: document.getElementById('hoekentGeoFortFout'), validatieFunctie: valideerHoeKentGeoFort },
        { veld: document.getElementById('vragenOpmerkingen'), foutElement: document.getElementById('vragenOpmerkingenFout'), validatieFunctie: valideerVragenenOpmerkingen }
    ];

    for (const veldInfo of optioneleVelden) {
        const waarde = veldInfo.veld.value.trim();
        if (waarde !== "") {
            const isGeldig = valideerInvoer(veldInfo.veld, veldInfo.foutElement, veldInfo.validatieFunctie);
            if (!isGeldig) {
                veldInfo.veld.focus();
                isFormulierGeldig = false;
                break;
            }
        }
    }

    const foodInputs = document.querySelectorAll('input[type="checkbox"][name="snack"], input[type="checkbox"][name="lunch"]');


    foodInputs.forEach(input => {
    const snackOption = input.closest('.snack-option');
    const amountInput = snackOption ? snackOption.querySelector('input[type="number"]') : null;


    if (input.checked && amountInput) {
        const foutElement = document.getElementById(`${amountInput.id}Fout`);
        const isValid = valideerInvoer(amountInput, foutElement, () => valideerInput(amountInput));
        if (!isValid) {
            amountInput.focus();
            isFormulierGeldig = false;
            return false;
        }
    } else if (!input.checked && amountInput) {
        amountInput.value = 0;
        amountInput.disabled = true;
    }
    });

    const schoolType = getGekozenOnderwijsNiveau();
    aanvraagscriptVerzendData.gekozenOnderwijsNiveau = schoolType;
    aanvraagscriptVerzendData.gekozenNiveaus = getNiveauValues();

    if (schoolType === "basis") {
    
        gekozenLeeftijdsGroepenPO = getLeeftijdsgroepenPOValues();
        aanvraagscriptVerzendData.gekozenLeeftijdsgroepenPO = gekozenLeeftijdsGroepenPO;

        const gekozenNiveaus = getNiveauValues();
        const validNiveaus = Object.keys(leeftijden[OnderwijsniveauSelect.value]);
        if (!validNiveaus.includes(gekozenNiveaus)) {
            isFormulierGeldig = false;
            niveauOptions.focus();
            valideerInvoer(niveauOptions, niveauFout, valideerOnderwijsSchooltypeNiveau);
            return false;
        } else if (!leeftijdDivPO || !leeftijdOptionsPO){
            niveauFout.textContent = "Ongeldige Selectie";
            valideerInvoer(niveauOptions, niveauFout, verzendknopFoutMelding)
        } else {
            const isValid = valideerInput(leeftijdOptionsPO, leeftijdOptionsPOFout, valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenPO)
            if (!isValid) {
                leeftijdOptionsPO.focus();
                isFormulierGeldig = false;
                return false;
            }

        } 
    } else if (schoolType === "voortgezet") {
        const gekozenSchooltype = OnderwijsniveauSelect.value;
        const gekozenNiveaus = getNiveauValues();
        const validNiveaus = Object.keys(leeftijden[gekozenSchooltype]); /** array */
        if (!gekozenNiveaus || !gekozenNiveaus || !validNiveaus) {
            const verzendknopDiv = document.getElementById("verzendknop");
            const verzendMeldingdiv = document.getElementById("verzendknopMeldingdiv");
            verzendknopDiv.value = "Ongeldige selectie gemaakt"
            valideerInvoer(verzendknopDiv, verzendMeldingdiv, verzendknopMelding);
        }

        if (gekozenNiveaus.length === 0) {
            isFormulierGeldig = false;
            niveauOptions.focus();
            valideerInvoer(niveauOptions, niveauFout, valideerOnderwijsSchooltypeNiveau);
            return false
        }

        const invalideNiveaus = gekozenNiveaus.filter(niveau => !validNiveaus.includes(niveau));

        if (invalideNiveaus && invalideNiveaus.length > 0) {
            niveauFout.textContent = "Ongeldige Selectie";
            niveauOptions.focus();
            valideerInvoer(niveauOptions, niveauFout, verzendknopFoutMelding)
            isFormulierGeldig = false;
            return false;
        }

        for (const niveau of gekozenNiveaus) {
            const selectVOleeftijdgroep = document.getElementById(`onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}`)
            if (!selectVOleeftijdgroep) {
                niveauFout.textContent = "Ongeldige Selectie";
                niveauOptions.focus();
                valideerInvoer(niveauOptions, niveauFout, verzendknopFoutMelding)
                isFormulierGeldig = false;
                return false;
            } 
            const selectVOleeftijdgroepFout = document.getElementById(`onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}Fout`);
            const isvalid = valideerInvoer(selectVOleeftijdgroep,selectVOleeftijdgroepFout,valideerOnderwijsSchooltypeNiveauLeeftijdsgroepenVO);
            if (!isvalid) {
                selectVOleeftijdgroep.focus();
                isFormulierGeldig = false;
                return false;
            }
        }
    }


    const formData = new FormData(this);

    const onderwijsSchooltypeNiveau = getNiveauValues();
    formData.delete("onderwijsSchooltypeNiveau[]");

    if (schoolType === "basis") {
        formData.append("onderwijsSchooltypeNiveau[]", onderwijsSchooltypeNiveau);
    } else if (schoolType === "voortgezet") {
        onderwijsSchooltypeNiveau.forEach(niveau => {
            formData.append("onderwijsSchooltypeNiveau[]", niveau);
        });
    }

    //formData.delete("onderwijsSchooltypeNiveau[]");



    if (schoolType === "basis") {
        gekozenLeeftijdsGroepenPO.forEach(leeftijd => {
            formData.append("onderwijsSchooltypeNiveauLeeftijdsGroepenPO[]", leeftijd);

        //formData.delete("onderwijsSchooltypeNiveauLeeftijdsGroepenPO[]")
        })
    } else if (schoolType === "voortgezet") {
        formData.delete("onderwijsSchooltypeNiveauLeeftijdsGroepenPO[]");
        onderwijsSchooltypeNiveau.forEach(niveau => {
            formData.delete(`leeftijdsgroepen_${niveau}[]`)
            const onderwijsSchooltypeNiveauLeeftijdsGroepenVO = document.getElementById(`onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}`);
            const onderwijsSchooltypeNiveauLeeftijdsGroepenVOSelectedOptions = Array.from(onderwijsSchooltypeNiveauLeeftijdsGroepenVO.selectedOptions).map(leeftijd => leeftijd.value);
            onderwijsSchooltypeNiveauLeeftijdsGroepenVOSelectedOptions.forEach(leeftijd => {
                formData.append(`onderwijsSchooltypeNiveauLeeftijdsGroepenVO_${niveau}[]`, leeftijd)
            })
        })

    }

    foodInputs.forEach(input => {
    const snackOption = input.closest('.snack-option');
    const amountInput = snackOption ? snackOption.querySelector('input[type="number"]') : null;

    // Controleer of de checkbox is aangevinkt en de 'name' niet gelijk is aan 'snack' of 'lunch'
    if (amountInput && input.checked && amountInput.name !== 'snack' && amountInput.name !== 'lunch') {
        formData.append(amountInput.name, amountInput.value);  // Voeg het aantal toe aan formData
    } else if (amountInput) {
        formData.append(amountInput.name, 0);  // Zet de waarde op 0 als het niet is aangevinkt
    } else if (input.id === 'eigenPicknickCheckbox') {
        formData.append('eigenPicknick', input.checked ? 1 : 0);  // Voeg eigenPicknick waarde toe
    }
    })

    formData.delete('snack');
    formData.delete('lunch');

    bezoekPrice = updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput)[0];
    foodPrice = updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput)[1];
    totalPrice = updateFoodSummary9(aantalBegeleidersInput, aantalLeerlingenInput)[2];

    if (typeof bezoekPrice === 'number' && bezoekPrice >= 0 && bezoekPrice <= 8000) {
    formData.append('bezoekPrice', bezoekPrice);
    }

    if (typeof foodPrice === 'number' && foodPrice >= 0 && foodPrice <= 4000) {
    formData.append('foodPrice', foodPrice)
    }

    if (typeof totalPrice === 'number' && totalPrice >= 0 && totalPrice <= 11000) {
    formData.append('totalPrice', totalPrice)
    }

    // Roep de functie aan bij het verzenden van het formulier
    verzendFormulier(formData, aanvraagscriptVerzendData);
});








