const date = new Date();
const ethioNow = ethCal.converterDateTime.toEthiopian(new Date());
let ethioDate  = ethioNow
document.querySelector(".calendar__picture h2").innerHTML = ethioNow.dateWithDayString

	const renderCalendar = () => {
		ethioDate = new ethCal.ethDateTime(1, ethioDate.month, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second);

		const monthDays = document.querySelector(".eth-cal-days")
		
		var prevLastDay = new ethCal.ethDateTime(1, ethioDate.month -1, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second).monthLength + 1
		if(ethioDate.month == 1){
			var prevLastDay = new ethCal.ethDateTime(1, 13, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second).monthLength + 1
		}
	
		document.querySelector(".calendar__picture h3").innerHTML = monthStringEth(ethioDate.month) + ' '+ ethioDate.year


		let days = "";

		for (let x = ethioDate.getDay(); x > 0; x--) {
			days += `<div class="prev-date calendar__number">${prevLastDay - x}</div>`
		}


		for (let i = 1; i<=ethioDate.monthLength; i++) {
			if(
				i=== ethioNow.date && 
				ethioNow.month === ethioDate.month && 
				ethioNow.year === ethioDate.year 
			){
				days += `<div class="calendar__number todayInEth">${i}</div>`
			}else{
				days += `<div class="calendar__number">${i}</div>`
			}
		}

		ethioDate = new ethCal.ethDateTime(ethioDate.monthLength, ethioDate.month, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second);

		var nextDays = 6 - ethioDate.getDay()
		for (let j = 1; j <= nextDays; j++) {
			days += `<div class="next-date calendar__number">${j}</div>`
		}
			monthDays.innerHTML = days;
	}


	document.querySelector(".eth-prev-month").
	addEventListener('click',()=>{
		if(ethioDate.month == 1){
			ethioDate = new ethCal.ethDateTime(1, 13, ethioDate.year - 1, ethioDate.hour, ethioDate.minute, ethioDate.second)
		}else{
			ethioDate = new ethCal.ethDateTime(1, ethioDate.month - 1, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second)
		}
		renderCalendar();
	})

	document.querySelector(".eth-next-month").
	addEventListener('click',()=>{
		if(ethioDate.month == 13){
			ethioDate = new ethCal.ethDateTime(1, 1, ethioDate.year + 1, ethioDate.hour, ethioDate.minute, ethioDate.second)
		}else{
			ethioDate = new ethCal.ethDateTime(1, ethioDate.month + 1, ethioDate.year, ethioDate.hour, ethioDate.minute, ethioDate.second)
		}
		renderCalendar();
	})

	document.querySelector(".todayInEth").
	addEventListener('click',()=>{
		ethioDate = ethCal.converterDateTime.toEthiopian(new Date());
		renderCalendar();
	})
	renderCalendar();