<?php
/**
 * Template Name: Program TV API template
 *
 * @package vodi
 */

 get_header();
?>

<div id="appEl">
	<table class="program-tv-table">
		<tr class="program-tv-table__welcome">
			<td>
				<h4>Aktualizacja programu (proszę czekać 10-60 s.)</h4>
			</td>
		</tr>
	</table>
</div>

<script>
// FUNKCJA ZWRACAJĄCA PRZYJAZNY, BARDZIEJ CZYTELNY DLA UŻYTKOWNIKA FORMAT DATY
function formatTime(clock) {
	const date = clock.slice(0, 8);
	const time = clock.slice(8, 14);

	const year = date.slice(0,4);
	const month = date.slice(4,6);
	const day = date.slice(6,8);

	const hour = time.slice(0,2);
	const minute = time.slice(2,4);
	const second = time.slice(4,6);

	const formatDate1 = {
		date: `${day}.${month}.${year}`,
		time: `${hour}:${minute}:${second}`
	};

	return formatDate1;
};

function renderContentInBrowser(programTV) {
	const appEl = document.getElementById('appEl');
	const programTVTableEl = document.querySelector('.program-tv-table');
	programTVTableEl.innerHTML = '';

	programTV.forEach(channel => {
		const trProgrammeItems = [];

		channel.programs.forEach(programme => {
			const startDate = formatTime(programme.start);
			const stopDate = formatTime(programme.stop);

			trProgrammeItems.push(`
				<tr>
					<td>${programme.title}</td>
					<td>${startDate.date} ${startDate.time}</td>
					<td>${programme.desc}</td>
				</tr>
			`)
		});

		const trChannelEl = `
			<tr bgcolor="#DBDB70" class="channel-container">
				<td>
					<h4>${channel.name}</h4>
				</td>
			</tr>
			<tr class="programme-container">
				<td>
					<table>
						<tr>
							<td>Tytuł</td>
							<td>Start</td>
							<td>Opis</td>
						</tr>
						${trProgrammeItems}
					</table>
				</td>
			</tr>
		`;

		// WRZUCAM WIERSZE DO TABELI
		programTVTableEl.insertAdjacentHTML('beforeend', trChannelEl);
	});
};

// ---
function renderTVDataToDOM(TVData) {
	const channels = TVData.getElementsByTagName('channel');
	const programme = TVData.getElementsByTagName('programme');

	const channelNamesArr = [];
	const programmesNamesArr = [];

	// WYSZUKUJĘ 10 1-SZYCH KANAŁÓW Z DANYCH JAKIE OTRZYMAŁEM Z API
	// I WRZUCAM JE DO TABLICY channelNamesArr
	for(let i = 0; i < 10; i++) {
		const channelName = channels[i].getElementsByTagName('display-name')[0].textContent;
		channelNamesArr.push(channelName);
	}

	// TWORZENIE I KOPIOWANIE CONTENTU Z XML DO JEDNEGO OBIEKTU
	// ŻEBY ŁATWIEJ OPEROWAĆ NA DANYCH
	const allChannels = [];

	channelNamesArr.forEach(channelName => {
		const channels = {
			name: channelName,
			programs: []
		};

		// WYSZUKIWANIE W 1000 1-SZYCH WYNIKACH PROGRAMÓW ŻEBY BYŁO KRÓCEJ
		for(let i = 0; i < 1000; i++) {
			const programmeItem = programme[i];
			const programmeName = programmeItem.getAttribute('channel');

			if(channelName === programmeName) {
				const prg = {
					title: programmeItem.getElementsByTagName('title')[0].textContent,
					start: programmeItem.getAttribute('start'),
					stop: programmeItem.getAttribute('stop'),
					desc: programmeItem.getElementsByTagName('desc')[0].textContent,
				};

				// SPRAWDZAM CZY PROGRAM JEST NA JUTRO
				const dayTVDate = parseInt(prg.start.slice(6,8));
				const currentDayDate = new Date().getDate();

				if(dayTVDate >= currentDayDate) {
					channels.programs.push(prg);
				}
			}
		}

		allChannels.push(channels);
	});

	// PRZEKAZUJE OBIEKT Z DANYMI PROGRAMU TV DO PONIŻSZEJ FUNKCJI
	renderContentInBrowser(allChannels);
};

function parseXML(dataXML) {
	if (window.DOMParser) {
		const parser = new DOMParser();
		const xmlDoc = parser.parseFromString(dataXML, "text/xml");
		return xmlDoc;
	} else {
		// for Internet Explorer
		var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async = false;
		return xmlDoc.loadXML(dataXML);
	}
};

// 2. ZAMIENIAM OTRZYMANE DANE NA TAKIE, ZA KTÓRYCH POMOCĄ MOGĘ OPEROWAĆ W JAVASCRIPT
// ZA POMOCĄ FUNKCJI parseXML I TO CO OTRZYMAM PRZEKAZUJĘ DO FUNKCJI renderTVDataToDOM, ABY WYSZUKAĆ ODPOWIEDNIE DANE
// W PROGRAMIE I POTEM WYRENDEROWAĆ JE W HTML DLA UŻYTKOWNIKA
function renderDataFromAPI(data) {
	const dataXML = new XMLSerializer().serializeToString(data);
	const TVData = parseXML(dataXML);
	renderTVDataToDOM(TVData);
};

// 1. POBIERAM DANE ZA POMOCĄ FETCH
function getDataFromAPI() {
  fetch('https://raw.githubusercontent.com/MajkiIT/kodi/master/EPG/epg_v2.xml?fbclid=IwAR2nNHi24kW8O2E_1onJcOIm2lV_JgpVBUvweOrBX7c7_k3LRZ-Nvpmh9Io')
  .then(response => response.text())
  .then(str => (new window.DOMParser()).parseFromString(str, "text/xml"))
	.then(data => renderDataFromAPI(data));
};

document.addEventListener('DOMContentLoaded', function() {
	getDataFromAPI();
});
</script>

<?php get_footer(); ?>