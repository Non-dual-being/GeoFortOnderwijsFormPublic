/**||-----[constanten]---------- || */

const messagePODownload = document.getElementById('PO-GFFormToDownload') || null;
const messageVO_onderDownload = document.getElementById('VO_onder-GFFormToDownload') ?? null;
const messageVO_bovenDownload = document.getElementById('VO_boven-GFFormToDownload') ?? null;
const messagePODownloadGrid = document.getElementById('PO-GFFormToDownload-item') || null;
const messageVO_onderDownloadGrid = document.getElementById('VO_onder-GFFormToDownload-item') ?? null;
const messageVO_bovenDownloadGrid = document.getElementById('VO_boven-GFFormToDownload-item') ?? null;
const downloadButtonPO = document.getElementById('PO-GFFormToDownload-download') ?? null;
const downloadButtonVO_onder = document.getElementById('VO_onder-GFFormToDownload-download') ?? null;
const downloadButtonVO_boven = document.getElementById('VO_boven-GFFormToDownload-download') ?? null;
let foutmeldingsTimer = null;

/**||-----[funcs]---------- || */
function toonFoutmelding(foutElement, foutmelding, element, duur = 4000) {

    foutElement.textContent = foutmelding;
    foutElement.className = "foute-invoermelding toonFoutMelding";
    foutElement.style.display= "flex";
    foutElement.style.opacity = '1';
    const rect = element.getBoundingClientRect();
    const scrollY = window.scrollY;
    foutElement.style.left = `${rect.left + window.scrollX}px`;
    foutElement.style.top = `${rect.top + scrollY - foutElement.offsetHeight - 5}px`;
    

    // de timer resetten en zorgen dat de foutmelding van de lopende timer wel verdwijnt voordat de nieuwe begint
    if (foutmeldingsTimer) {
        foutmeldingsTimer = setTimeout(() => {
            hideFoutmelding(foutElement)
        }, 2500)
    } else {
        foutmeldingsTimer = setTimeout(() => {
            hideFoutmelding(foutElement)
        }, duur);

    }

}

// Verberg foutmelding en herstel de styling van het invoerveld
function hideFoutmelding(foutElement) {
    foutElement.style.opacity = '0'; // Start fade-out effect
    setTimeout(() => {
        foutElement.style.display = 'none'; // Verberg volledig
        foutElement.textContent = ""; // Wis de foutmelding
        foutElement.className = 'foute-invoermelding';
    }, 600);
    foutmeldingsTimer = null;
}


if (messagePODownload && messagePODownload.textContent) {
    toonFoutmelding(messagePODownload, messagePODownload.textContent, messagePODownloadGrid);
}

if (messageVO_onderDownload && messageVO_onderDownload.textContent) {
    toonFoutmelding(messageVO_onderDownload, messageVO_onderDownload.textContent,messageVO_onderDownloadGrid);
}

if (messageVO_bovenDownload && messageVO_bovenDownload.textContent) {

    toonFoutmelding(messageVO_bovenDownload, messageVO_bovenDownload.textContent, messageVO_bovenDownloadGrid);

}

if(downloadButtonPO){
    downloadButtonPO.addEventListener('click', function(){
        let checkDownload = setInterval(
            function(){
                if(document.cookie.includes("download_started=1")){
                    clearInterval(checkDownload);
                    if (messagePODownload) {
                        toonFoutmelding(messagePODownload, "Onderwijs aanvraagformulier is gedownload", messagePODownloadGrid);
                    }
                    document.cookie = "download_started=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    
                }
            }, 500)
    })
}

if(downloadButtonVO_onder){
    downloadButtonVO_onder.addEventListener('click', function(){
        let checkDownload = setInterval(
            function(){
                if(document.cookie.includes("download_started=1")){
                    clearInterval(checkDownload);
                    if (messageVO_onderDownload) {
                        toonFoutmelding(messageVO_onderDownload, "Onderwijs aanvraagformulier is gedownload", messageVO_onderDownloadGrid);
                    }
                    document.cookie = "download_started=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    
                }
            }, 500)
    })
}
if(downloadButtonVO_boven){
    downloadButtonVO_boven.addEventListener('click', function(){
        let checkDownload = setInterval(
            function(){
                if(document.cookie.includes("download_started=1")){
                    clearInterval(checkDownload);
                    if (messageVO_bovenDownload) {
                        toonFoutmelding(messageVO_bovenDownload, "Onderwijs aanvraagformulier is gedownload", messageVO_bovenDownloadGrid);
                    }
                    document.cookie = "download_started=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    
                }
            }, 200)
    })
}