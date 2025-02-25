//imports
import { resetDayStyles, applyDayStyles, getISODate } from "./db_module_StyleCalender";


//Flatpick initialiseren
flatpickr.localize(flatpickr.l10ns.nl);


/** || -------------[constants]------------|| */
//html elementen 
const startDateCalenderMessage = document.getElementById('receive-message-startcalender');
const endDateCalender = document.getElementById('end_date');
const downloadButton = document.getElementById("download-button");
const generalMessage = document.getElementById('receive-message-general');
const messageSumbitDataRange = document.getElementById('receive-message-calenderSubmitKnop');
const donwloadMessage = document.getElementById('download-button-message');




//gebruikersvariabelen 
const dataRangeReceivedFromServer = window.calenderData[0] || [];
const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
const dataSetUsedData = {
    startDate: "",
    endDate: ""
}
const selectedDatesDataSet = {
    selectedStartDate: null,
    selectedEndDate: null
}
const noAbsolutePositionIDSArray = ['receive-message-startcalender', 'receive-message-general', 'downloadRequests-button-message'];




/**||-------------------------[functies] -----------------------|| */

//bericht voor een duur laten zien en naar toe scrollen
function showFlashMessage(flashMessage, duration = 5000, text = '') {
    if (!flashMessage) return;
    const elementId = flashMessage.id;
    if (!elementId) return;

    if (text !==''){    
        flashMessage.textContent = String(text.trim()).replace(/[";]+/g, '');
    }
    if (flashMessage && flashMessage.textContent.trim() !== '') {
        if (text !== ''){
            flashMessage.style.display = 'flex';
            flashMessage.classList.add('success');
        } else {
            flashMessage.style.display = 'flex';
            if (!noAbsolutePositionIDSArray.includes(elementId)) {
                //zit het element niet in de lijst val items die geen absolute positie mogen dan absoluut positioneren
                if (elementId !== 'change-number-message') {
                flashMessage.classList.add('absolute-positioned-General');
                } 
            } else {
                if (elementId !== 'downloadRequests-button-message'){
                    flashMessage.classList.add('flash-message-alternative');
                }
            }
        }
        flashMessage.scrollIntoView({behavior:"smooth", block: "center"});
        if (elementId !== 'receive-message-general'){
            setTimeout(() => {
                flashMessage.classList.add('fade-out'); // Start fade-out effect
                setTimeout(() => {
                    flashMessage.style.display = 'none'; // Verberg na de fade
                    flashMessage.classList.remove('fade-out'); // Reset voor hergebruik
                }, 1000); // Tijd overeenkomend met de CSS-transitie
            }, duration);
        }
    }
}




function initFlatpickrStartDate(dateRange = {}) {
    let dateRangeProvided = null;

    if (Object.keys(dateRange).length > 0){
        dataSetUsedData.startDate = dateRange.startdate;
        dataSetUsedData.endDate = dateRange.enddate;
        dateRangeProvided = true;
    } else {
        dataSetUsedData.startDate = '';
        dataSetUsedData.endDate = '';
        dateRangeProvided = false;
    }
    

    flatpickr("#start_date", {
        locale: "nl",
        dateFormat: "d F Y",
        weekNumbers: true,
        enableTime: false,
        disable: [ 
            function (date) {
                if (dateRangeProvided){
                    const isoDate = getISODate(date);
                    const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
                    const outOfrange =  isoDate < dataSetUsedData.startDate || isoDate > dataSetUsedData.endDate;
                    const isDisabled = isWeekend || outOfrange
                    return isDisabled;

                } else {
                    return date;
                }
        
            }
        ],
        appendTo: document.body,
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            resetDayStyles(dayElem);
            if (!dayElem.classList.contains('flatpickr-disabled')){
                applyDayStyles(dayElem, '', 'downloaddataset')

            dayElem.addEventListener('mouseenter', () => {
                dayElem.classList.add('hoverOverCalenderDate');
            });
            dayElem.addEventListener('mouseleave', () => {
                dayElem.classList.remove('hoverOverCalenderDate');
            });

            if (dayElem.classList.contains('selected')) {
                dayElem.classList.add('calendar-day.Selected.extraLayer');
            }

        }
        },
        onChange: function(selectedDates) {
            if (selectedDates.length > 0) {
                //gekozen datum gelijk stellen als begin datum
                selectedDatesDataSet.selectedStartDate = selectedDates[0];

                //dataRange als object met twee sql dates doorsturen naar de eindcalender
                const dataRangeForEndDateCalender = {
                    startdate: getISODate(new Date(selectedDates[0])),
                    enddate: dataSetUsedData.endDate
                }
    
                initFlatpickerEndDate(dataRangeForEndDateCalender);           
            }
        },
        firstDayOfWeek: 1
    });
}


function initFlatpickerEndDate(dateRange) {
    if (!dateRange) {
        return;
    }

    flatpickr("#end_date", {
        locale: "nl",
        dateFormat: "d F Y",
        weekNumbers: true,
        enableTime: false,
        disable: [ 
            function (date) {
                const isoDate = getISODate(date);
                const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
                const outOfrange =  isoDate < dateRange.startdate || isoDate > dateRange.enddate
                const isDisabled = isWeekend || outOfrange
                return isDisabled;
            }
        ],
        appendTo: document.body,
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            resetDayStyles(dayElem);
            if (!dayElem.classList.contains('flatpickr-disabled')){
                applyDayStyles(dayElem, '', 'downloaddataset')

                dayElem.addEventListener('mouseenter', () => {
                    dayElem.classList.add('hoverOverCalenderDate');
                });
                dayElem.addEventListener('mouseleave', () => {
                    dayElem.classList.remove('hoverOverCalenderDate');
                });

                if (dayElem.classList.contains('selected')) {
                    dayElem.classList.add('calendar-day.Selected.extraLayer');
                }
     
            }
        },
        onChange: function(selectedDates) {
            if (selectedDates.length > 0) {
                selectedDatesDataSet.selectedEndDate = getISODate(new Date(selectedDates[0]));  
            }
        },

        firstDayOfWeek: 1
    });
}


//startkalender initialiseren met data van de server
if (calenderData && dateRegex.test(dataRangeReceivedFromServer.enddate) && dateRegex.test(dataRangeReceivedFromServer.startdate)){
    initFlatpickrStartDate(dataRangeReceivedFromServer)
} else {
    initFlatpickrStartDate();
}


endDateCalender.addEventListener("click", function () {
    if (selectedDatesDataSet.selectedStartDate === null) {
        showFlashMessage(messageSumbitDataRange, 6000, "Kies eerst een startdatum");
    }   
})

/**||--------[berichten van de server laten zien met showFlashMessage]----|| */

if (startDateCalenderMessage){
    showFlashMessage(startDateCalenderMessage);
}

if (generalMessage){
    showFlashMessage(generalMessage);
}

if (messageSumbitDataRange){
    showFlashMessage(messageSumbitDataRange);
}

if (donwloadMessage){
    showFlashMessage(donwloadMessage);
}


const toggle = document.getElementById("meerInformatieDataset");

toggle.addEventListener("click", function(event) {
    event.preventDefault(); // Voorkom dat de pagina scrollt naar de top
    
    // Haal het doel-element op via de data-target attribuut
    const targetId = toggle.getAttribute("data-target");

    const targetContent = document.getElementById(targetId);
    
    // Toggle de "open" class om de inhoud te tonen of verbergen
    targetContent.classList.toggle("open");

    // Pas de tekst van de toggle-link aan
    if (targetContent.classList.contains("open")) {
        toggle.querySelector("span").textContent = "Verberg";
    } else {
        // Voeg hier de check toe voor bezoektijdenInfo
        if (targetId === "datum_kiezen_dataset") {
            toggle.querySelector("span").textContent = "Meer informatie over het verkrijgen van een dataset";
        } 
    }
});

if (downloadButton){
    downloadButton.addEventListener("click", function(){
        let checkDownload = setInterval(
            function(){
                if (document.cookie.includes("download_started=1")){
                    clearInterval(checkDownload);
                    const flashMessage = document.getElementById("donwload-started-message");
                    if (flashMessage){
                        showFlashMessage("donwload-started-message", 5000, "bestanden zijn gedownload");
                    }
                    document.cookie = "download_started=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                }
            }, 500)
    })
}




   
