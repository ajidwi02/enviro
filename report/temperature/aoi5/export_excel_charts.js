/**
 * ==============================================================================
 * Export Excel with Multi-Granularity Charts Engine
 * Project: Enviro Monitoring System - AOI 5
 * Target: report/temperature/aoi5/
 * Author: PT Apparel One Indonesia
 * ==============================================================================
 */

var TEMP_SAFE_LIMIT = 30.0;
var HUM_SAFE_LIMIT = 60.0;

/**
 * Render High-Resolution Dual-Axis Line Chart to an Off-Screen HTML5 Canvas
 */
function renderDualAxisChartCanvas(title, subtitle, labels, datasets, thresholds, width, height) {
    width = width || 1200;
    height = height || 480;

    var canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    var ctx = canvas.getContext('2d');

    // 1. Latar Belakang Putih Bersih
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, width, height);

    // Border Halus Canvas
    ctx.strokeStyle = '#E2E8F0';
    ctx.lineWidth = 1;
    ctx.strokeRect(0, 0, width, height);

    // 2. Judul & Sub-judul
    ctx.fillStyle = '#1E293B';
    ctx.font = 'bold 18px "Segoe UI", Arial, sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText(title, 40, 32);

    ctx.fillStyle = '#64748B';
    ctx.font = '12px "Segoe UI", Arial, sans-serif';
    ctx.fillText(subtitle, 40, 52);

    // Batas Area Plot
    var pLeft = 80;
    var pRight = width - 80;
    var pTop = 75;
    var pBottom = height - 75;
    var pWidth = pRight - pLeft;
    var pHeight = pBottom - pTop;

    // Skala Sumbu Y
    // Sumbu Kiri: Suhu (°C) -> Rentang 10°C s/d 40°C
    var minTemp = 10, maxTemp = 40;
    // Sumbu Kanan: Kelembapan (%) -> Rentang 20% s/d 100%
    var minHum = 20, maxHum = 100;

    function getYLeft(temp) {
        if (temp === null || temp === undefined || isNaN(temp)) return null;
        var clamped = Math.max(minTemp, Math.min(maxTemp, temp));
        return pBottom - ((clamped - minTemp) / (maxTemp - minTemp)) * pHeight;
    }

    function getYRight(hum) {
        if (hum === null || hum === undefined || isNaN(hum)) return null;
        var clamped = Math.max(minHum, Math.min(maxHum, hum));
        return pBottom - ((clamped - minHum) / (maxHum - minHum)) * pHeight;
    }

    function getX(index, total) {
        if (total <= 1) return pLeft + pWidth / 2;
        return pLeft + (index / (total - 1)) * pWidth;
    }

    // 3. Gridlines Horizontal & Label Sumbu Y
    var gridSteps = 6;
    for (var i = 0; i <= gridSteps; i++) {
        var ratio = i / gridSteps;
        var gy = pBottom - ratio * pHeight;
        var tempVal = Math.round((minTemp + ratio * (maxTemp - minTemp)) * 10) / 10;
        var humVal = Math.round(minHum + ratio * (maxHum - minHum));

        ctx.strokeStyle = (i === 0 || i === gridSteps) ? '#CBD5E1' : '#F1F5F9';
        ctx.lineWidth = 1;
        ctx.setLineDash([]);
        ctx.beginPath();
        ctx.moveTo(pLeft, gy);
        ctx.lineTo(pRight, gy);
        ctx.stroke();

        // Label Suhu Sumbu Kiri
        ctx.fillStyle = '#475569';
        ctx.font = '11px "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(tempVal + '°C', pLeft - 10, gy + 4);

        // Label Kelembapan Sumbu Kanan
        ctx.textAlign = 'left';
        ctx.fillText(humVal + '%', pRight + 10, gy + 4);
    }

    // Judul Sumbu Y
    ctx.fillStyle = '#1E3A8A';
    ctx.font = 'bold 11px "Segoe UI", Arial, sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText('Suhu (°C)', pLeft - 10, pTop - 10);

    ctx.fillStyle = '#0E7490';
    ctx.textAlign = 'left';
    ctx.fillText('Humid (%)', pRight + 10, pTop - 10);

    // 4. Garis Batas Acuan Aman (Threshold Reference Lines)
    var tempLimit = (thresholds && thresholds.tempLimit !== undefined) ? thresholds.tempLimit : 30.0;
    var humLimit = (thresholds && thresholds.humLimit !== undefined) ? thresholds.humLimit : 60.0;

    // Batas Suhu 30°C: Garis Merah Solid
    var yTempLimit = getYLeft(tempLimit);
    if (yTempLimit !== null) {
        ctx.strokeStyle = '#DC2626';
        ctx.lineWidth = 2;
        ctx.setLineDash([]);
        ctx.beginPath();
        ctx.moveTo(pLeft, yTempLimit);
        ctx.lineTo(pRight, yTempLimit);
        ctx.stroke();

        ctx.fillStyle = '#DC2626';
        ctx.font = 'bold 10px "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('▲ BATAS ACUAN SUHU: ' + tempLimit.toFixed(1) + '°C', pLeft + 15, yTempLimit - 5);
    }

    // Batas Humidity 60%: Garis Merah Putus-putus
    var yHumLimit = getYRight(humLimit);
    if (yHumLimit !== null) {
        ctx.strokeStyle = '#B91C1C';
        ctx.lineWidth = 2;
        ctx.setLineDash([8, 5]);
        ctx.beginPath();
        ctx.moveTo(pLeft, yHumLimit);
        ctx.lineTo(pRight, yHumLimit);
        ctx.stroke();
        ctx.setLineDash([]);

        ctx.fillStyle = '#B91C1C';
        ctx.font = 'bold 10px "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('▲ BATAS ACUAN HUMIDITY: ' + humLimit.toFixed(1) + '%', pRight - 15, yHumLimit - 5);
    }

    // 5. Garis Dasar Sumbu X & Label Waktu
    ctx.strokeStyle = '#94A3B8';
    ctx.lineWidth = 1.5;
    ctx.setLineDash([]);
    ctx.beginPath();
    ctx.moveTo(pLeft, pBottom);
    ctx.lineTo(pRight, pBottom);
    ctx.stroke();

    var totalPoints = labels.length;
    var step = Math.max(1, Math.ceil(totalPoints / 12));

    ctx.fillStyle = '#64748B';
    ctx.font = '11px "Segoe UI", Arial, sans-serif';
    ctx.textAlign = 'center';

    for (var j = 0; j < totalPoints; j++) {
        if (j % step === 0 || j === totalPoints - 1) {
            var xPos = getX(j, totalPoints);
            ctx.beginPath();
            ctx.moveTo(xPos, pBottom);
            ctx.lineTo(xPos, pBottom + 4);
            ctx.stroke();
            ctx.fillText(labels[j], xPos, pBottom + 18);
        }
    }

    // 6. Menggambar Kurva Garis Data
    datasets.forEach(function (ds) {
        if (!ds.data || ds.data.length === 0) return;

        ctx.strokeStyle = ds.color || '#3B82F6';
        ctx.lineWidth = ds.lineWidth || 2.5;
        if (ds.dashed) {
            ctx.setLineDash(ds.dashed);
        } else {
            ctx.setLineDash([]);
        }

        var hasStarted = false;
        ctx.beginPath();
        for (var k = 0; k < ds.data.length; k++) {
            var val = ds.data[k];
            if (val === null || val === undefined || isNaN(val)) {
                hasStarted = false;
                continue;
            }
            var yCoord = (ds.axis === 'right') ? getYRight(val) : getYLeft(val);
            var xCoord = getX(k, totalPoints);

            if (!hasStarted) {
                ctx.moveTo(xCoord, yCoord);
                hasStarted = true;
            } else {
                ctx.lineTo(xCoord, yCoord);
            }
        }
        ctx.stroke();
        ctx.setLineDash([]);

        // Gambar titik marker jika data tidak terlalu padat (<= 60 titik)
        if (totalPoints <= 60) {
            for (var m = 0; m < ds.data.length; m++) {
                var pVal = ds.data[m];
                if (pVal === null || pVal === undefined || isNaN(pVal)) continue;
                var py = (ds.axis === 'right') ? getYRight(pVal) : getYLeft(pVal);
                var px = getX(m, totalPoints);

                ctx.fillStyle = ds.color || '#3B82F6';
                ctx.beginPath();
                if (ds.marker === 'square') {
                    ctx.fillRect(px - 3, py - 3, 6, 6);
                } else if (ds.marker === 'triangle') {
                    ctx.moveTo(px, py - 4);
                    ctx.lineTo(px + 4, py + 4);
                    ctx.lineTo(px - 4, py + 4);
                    ctx.closePath();
                    ctx.fill();
                } else if (ds.marker === 'diamond') {
                    ctx.moveTo(px, py - 4);
                    ctx.lineTo(px + 4, py);
                    ctx.lineTo(px, py + 4);
                    ctx.lineTo(px - 4, py);
                    ctx.closePath();
                    ctx.fill();
                } else {
                    ctx.arc(px, py, 3.5, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }
    });

    // 7. Legenda di Bagian Bawah
    var legendY = height - 25;
    var legendItems = [
        { label: 'Suhu Folding (501)', color: '#E67E22', type: 'circle' },
        { label: 'Humid Folding (501)', color: '#2980B9', type: 'square' },
        { label: 'Suhu Dry Room (502)', color: '#F39C12', type: 'triangle' },
        { label: 'Humid Dry Room (502)', color: '#16A085', type: 'diamond' },
        { label: 'Batas Suhu (30°C)', color: '#DC2626', type: 'solid_line' },
        { label: 'Batas Humid (60%)', color: '#B91C1C', type: 'dash_line' }
    ];

    var totalLegendWidth = 0;
    ctx.font = '11px "Segoe UI", Arial, sans-serif';
    legendItems.forEach(function (item) {
        totalLegendWidth += ctx.measureText(item.label).width + 36;
    });

    var startLX = Math.max(30, (width - totalLegendWidth) / 2);
    legendItems.forEach(function (item) {
        ctx.fillStyle = item.color;
        ctx.strokeStyle = item.color;
        ctx.lineWidth = 2;

        if (item.type === 'circle') {
            ctx.beginPath();
            ctx.arc(startLX + 8, legendY - 4, 4, 0, Math.PI * 2);
            ctx.fill();
        } else if (item.type === 'square') {
            ctx.fillRect(startLX + 4, legendY - 8, 8, 8);
        } else if (item.type === 'triangle') {
            ctx.beginPath();
            ctx.moveTo(startLX + 8, legendY - 9);
            ctx.lineTo(startLX + 12, legendY - 1);
            ctx.lineTo(startLX + 4, legendY - 1);
            ctx.closePath();
            ctx.fill();
        } else if (item.type === 'diamond') {
            ctx.beginPath();
            ctx.moveTo(startLX + 8, legendY - 9);
            ctx.lineTo(startLX + 12, legendY - 5);
            ctx.lineTo(startLX + 8, legendY - 1);
            ctx.lineTo(startLX + 4, legendY - 5);
            ctx.closePath();
            ctx.fill();
        } else if (item.type === 'solid_line') {
            ctx.beginPath();
            ctx.moveTo(startLX + 2, legendY - 4);
            ctx.lineTo(startLX + 16, legendY - 4);
            ctx.stroke();
        } else if (item.type === 'dash_line') {
            ctx.setLineDash([4, 2]);
            ctx.beginPath();
            ctx.moveTo(startLX + 2, legendY - 4);
            ctx.lineTo(startLX + 16, legendY - 4);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        ctx.fillStyle = '#334155';
        ctx.textAlign = 'left';
        ctx.fillText(item.label, startLX + 20, legendY);

        startLX += ctx.measureText(item.label).width + 36;
    });

    return canvas.toDataURL('image/png');
}

/**
 * Normalisasi dan Agregasi Data 4 Dimensi Waktu
 */
function processReportData(rawRecords) {
    var records = [];

    // 1. Ekstraksi dan sanitasi data
    for (var i = 0; i < rawRecords.length; i++) {
        var r = rawRecords[i];
        var idLoc = (r.id_location || r[2] || '').toString().trim();
        var locLower = idLoc.toLowerCase();
        var locName = (r.location || r[3] || '').toString().trim();

        var is501 = (locLower === '501' || locLower === 'area501' || locLower === 'area_1' || locLower === 'temp1' || locName.toLowerCase().indexOf('folding') !== -1);
        var is502 = (locLower === '502' || locLower === 'area502' || locLower === 'area_2' || locLower === 'temp2' || locName.toLowerCase().indexOf('dry') !== -1);

        if (!is501 && !is502) {
            // Default mapping
            if (locLower.indexOf('501') !== -1) is501 = true;
            else if (locLower.indexOf('502') !== -1) is502 = true;
            else continue;
        }

        var tempVal = parseFloat(r.temp !== undefined ? r.temp : r[4]);
        var humVal = parseFloat(r.humidity !== undefined ? r.humidity : r[5]);
        var recTime = (r.record_time || r[7] || '').toString().trim();
        if (!recTime) continue;

        var weather = (r.weather_status || r[6] || '-').toString().trim();
        var dayName = (r.day_name || r[8] || '').toString().trim();

        records.push({
            id_location: is501 ? '501' : '502',
            location_name: is501 ? 'Folding Area' : 'Dry Room',
            is501: is501,
            is502: is502,
            temp: isNaN(tempVal) ? null : Math.round(tempVal * 10) / 10,
            humidity: isNaN(humVal) ? null : Math.round(humVal * 10) / 10,
            weather_status: weather,
            record_time: recTime,
            day_name: dayName,
            is_alert: (tempVal > TEMP_SAFE_LIMIT || humVal > HUM_SAFE_LIMIT)
        });
    }

    // Urutkan data secara kronologis (dari waktu terlama ke terbaru)
    records.sort(function (a, b) {
        return new Date(a.record_time) - new Date(b.record_time);
    });

    // 2. Agregasi Granularitas 1: Per Jam (Hourly)
    var hourlyMap = {};
    var hourlyKeys = [];

    records.forEach(function (rec) {
        var hourKey = rec.record_time.substring(0, 13) + ':00'; // YYYY-MM-DD HH:00
        if (!hourlyMap[hourKey]) {
            hourlyMap[hourKey] = {
                key: hourKey,
                datePart: rec.record_time.substring(0, 10),
                hourPart: rec.record_time.substring(11, 16),
                temp501: null,
                hum501: null,
                temp502: null,
                hum502: null,
                weather: rec.weather_status
            };
            hourlyKeys.push(hourKey);
        }
        if (rec.is501) {
            hourlyMap[hourKey].temp501 = rec.temp;
            hourlyMap[hourKey].hum501 = rec.humidity;
        } else if (rec.is502) {
            hourlyMap[hourKey].temp502 = rec.temp;
            hourlyMap[hourKey].hum502 = rec.humidity;
        }
        if (rec.weather_status && rec.weather_status !== '-') {
            hourlyMap[hourKey].weather = rec.weather_status;
        }
    });

    hourlyKeys.sort();

    var hourlyLabels = [];
    var hourlyT501 = [], hourlyH501 = [];
    var hourlyT502 = [], hourlyH502 = [];

    hourlyKeys.forEach(function (k) {
        var hObj = hourlyMap[k];
        // Format label: "DD/MM HH:mm"
        var dParts = hObj.datePart.split('-');
        var displayLabel = (dParts.length === 3 ? (dParts[2] + '/' + dParts[1]) : hObj.datePart) + ' ' + hObj.hourPart;
        hourlyLabels.push(displayLabel);
        hourlyT501.push(hObj.temp501);
        hourlyH501.push(hObj.hum501);
        hourlyT502.push(hObj.temp502);
        hourlyH502.push(hObj.hum502);
    });

    // 3. Agregasi Granularitas 2: Per Hari (Daily)
    var dailyMap = {};
    var dailyKeys = [];

    records.forEach(function (rec) {
        var dateKey = rec.record_time.substring(0, 10); // YYYY-MM-DD
        if (!dailyMap[dateKey]) {
            dailyMap[dateKey] = {
                date: dateKey,
                dayName: rec.day_name,
                t501: [], h501: [],
                t502: [], h502: [],
                weatherList: []
            };
            dailyKeys.push(dateKey);
        }
        if (rec.is501) {
            if (rec.temp !== null) dailyMap[dateKey].t501.push(rec.temp);
            if (rec.humidity !== null) dailyMap[dateKey].h501.push(rec.humidity);
        } else if (rec.is502) {
            if (rec.temp !== null) dailyMap[dateKey].t502.push(rec.temp);
            if (rec.humidity !== null) dailyMap[dateKey].h502.push(rec.humidity);
        }
        if (rec.weather_status && rec.weather_status !== '-') {
            dailyMap[dateKey].weatherList.push(rec.weather_status);
        }
    });

    dailyKeys.sort();

    function calcStats(arr) {
        if (!arr || arr.length === 0) return { avg: null, min: null, max: null };
        var sum = 0, min = arr[0], max = arr[0];
        for (var idx = 0; idx < arr.length; idx++) {
            var v = arr[idx];
            sum += v;
            if (v < min) min = v;
            if (v > max) max = v;
        }
        return {
            avg: Math.round((sum / arr.length) * 10) / 10,
            min: min,
            max: max
        };
    }

    var dailyLabels = [];
    var dailyT501 = [], dailyH501 = [];
    var dailyT502 = [], dailyH502 = [];
    var dailySummaryRows = [];

    dailyKeys.forEach(function (dKey) {
        var dObj = dailyMap[dKey];
        var s501T = calcStats(dObj.t501);
        var s501H = calcStats(dObj.h501);
        var s502T = calcStats(dObj.t502);
        var s502H = calcStats(dObj.h502);

        var dParts = dKey.split('-');
        var displayDate = dParts.length === 3 ? (dParts[2] + '/' + dParts[1] + '/' + dParts[0]) : dKey;
        dailyLabels.push(dParts.length === 3 ? (dParts[2] + '/' + dParts[1]) : dKey);

        dailyT501.push(s501T.avg);
        dailyH501.push(s501H.avg);
        dailyT502.push(s502T.avg);
        dailyH502.push(s502H.avg);

        // Cari cuaca dominan
        var domWeather = '-';
        if (dObj.weatherList.length > 0) {
            var counts = {};
            dObj.weatherList.forEach(function (w) { counts[w] = (counts[w] || 0) + 1; });
            var maxC = 0;
            for (var wName in counts) {
                if (counts[wName] > maxC) {
                    maxC = counts[wName];
                    domWeather = wName;
                }
            }
        }

        var isDayExceed = (s501T.max > TEMP_SAFE_LIMIT || s502T.max > TEMP_SAFE_LIMIT || s501H.max > HUM_SAFE_LIMIT || s502H.max > HUM_SAFE_LIMIT);

        dailySummaryRows.push({
            date: displayDate,
            dayName: dObj.dayName,
            avgT501: s501T.avg !== null ? s501T.avg : '-',
            rangeT501: s501T.min !== null ? (s501T.min + ' - ' + s501T.max) : '-',
            avgH501: s501H.avg !== null ? s501H.avg : '-',
            avgT502: s502T.avg !== null ? s502T.avg : '-',
            rangeT502: s502T.min !== null ? (s502T.min + ' - ' + s502T.max) : '-',
            avgH502: s502H.avg !== null ? s502H.avg : '-',
            weather: domWeather,
            status: isDayExceed ? 'ALERT (> Batas)' : 'AMAN'
        });
    });

    // 4. Agregasi Granularitas 3: Per Minggu (Weekly)
    var weeklyMap = {};
    var weeklyKeys = [];

    records.forEach(function (rec) {
        // Kelompokkan 7 hari
        var recDate = new Date(rec.record_time);
        var oneJan = new Date(recDate.getFullYear(), 0, 1);
        var numberOfDays = Math.floor((recDate - oneJan) / (24 * 60 * 60 * 1000));
        var weekNum = Math.ceil((numberOfDays + oneJan.getDay() + 1) / 7);
        var weekKey = recDate.getFullYear() + '-W' + (weekNum < 10 ? '0' + weekNum : weekNum);

        if (!weeklyMap[weekKey]) {
            weeklyMap[weekKey] = { key: weekKey, t501: [], h501: [], t502: [], h502: [] };
            weeklyKeys.push(weekKey);
        }
        if (rec.is501) {
            if (rec.temp !== null) weeklyMap[weekKey].t501.push(rec.temp);
            if (rec.humidity !== null) weeklyMap[weekKey].h501.push(rec.humidity);
        } else if (rec.is502) {
            if (rec.temp !== null) weeklyMap[weekKey].t502.push(rec.temp);
            if (rec.humidity !== null) weeklyMap[weekKey].h502.push(rec.humidity);
        }
    });

    weeklyKeys.sort();

    var weeklyLabels = [];
    var weeklyT501 = [], weeklyH501 = [];
    var weeklyT502 = [], weeklyH502 = [];

    weeklyKeys.forEach(function (wKey) {
        var wObj = weeklyMap[wKey];
        var wParts = wKey.split('-');
        weeklyLabels.push('Minggu ' + wParts[1]);
        weeklyT501.push(calcStats(wObj.t501).avg);
        weeklyH501.push(calcStats(wObj.h501).avg);
        weeklyT502.push(calcStats(wObj.t502).avg);
        weeklyH502.push(calcStats(wObj.h502).avg);
    });

    // 5. Agregasi Granularitas 4: Per Bulan (Monthly)
    var monthlyMap = {};
    var monthlyKeys = [];

    records.forEach(function (rec) {
        var mKey = rec.record_time.substring(0, 7); // YYYY-MM
        if (!monthlyMap[mKey]) {
            monthlyMap[mKey] = { key: mKey, t501: [], h501: [], t502: [], h502: [] };
            monthlyKeys.push(mKey);
        }
        if (rec.is501) {
            if (rec.temp !== null) monthlyMap[mKey].t501.push(rec.temp);
            if (rec.humidity !== null) monthlyMap[mKey].h501.push(rec.humidity);
        } else if (rec.is502) {
            if (rec.temp !== null) monthlyMap[mKey].t502.push(rec.temp);
            if (rec.humidity !== null) monthlyMap[mKey].h502.push(rec.humidity);
        }
    });

    monthlyKeys.sort();

    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var monthlyLabels = [];
    var monthlyT501 = [], monthlyH501 = [];
    var monthlyT502 = [], monthlyH502 = [];

    monthlyKeys.forEach(function (mKey) {
        var mObj = monthlyMap[mKey];
        var mParts = mKey.split('-');
        var mIndex = parseInt(mParts[1], 10) - 1;
        var displayMonth = (mIndex >= 0 && mIndex < 12 ? monthNames[mIndex] : mParts[1]) + ' ' + mParts[0];

        monthlyLabels.push(displayMonth);
        monthlyT501.push(calcStats(mObj.t501).avg);
        monthlyH501.push(calcStats(mObj.h501).avg);
        monthlyT502.push(calcStats(mObj.t502).avg);
        monthlyH502.push(calcStats(mObj.h502).avg);
    });

    // 6. Hitung Statistik Ringkasan Keseluruhan (KPI Cards)
    var allT501 = [], allH501 = [];
    var allT502 = [], allH502 = [];
    var totalRecords = records.length;
    var safeRecordsCount = 0;
    var exceedTempCount = 0;
    var exceedHumCount = 0;

    records.forEach(function (rec) {
        if (rec.is501) {
            if (rec.temp !== null) allT501.push(rec.temp);
            if (rec.humidity !== null) allH501.push(rec.humidity);
        } else if (rec.is502) {
            if (rec.temp !== null) allT502.push(rec.temp);
            if (rec.humidity !== null) allH502.push(rec.humidity);
        }

        var tExceed = (rec.temp !== null && rec.temp > TEMP_SAFE_LIMIT);
        var hExceed = (rec.humidity !== null && rec.humidity > HUM_SAFE_LIMIT);

        if (tExceed) exceedTempCount++;
        if (hExceed) exceedHumCount++;
        if (!tExceed && !hExceed) safeRecordsCount++;
    });

    var kpi501T = calcStats(allT501);
    var kpi501H = calcStats(allH501);
    var kpi502T = calcStats(allT502);
    var kpi502H = calcStats(allH502);

    var complianceRate = totalRecords > 0 ? Math.round((safeRecordsCount / totalRecords) * 1000) / 10 : 100.0;

    return {
        records: records,
        kpi: {
            totalRecords: totalRecords,
            safeRecordsCount: safeRecordsCount,
            exceedCount: totalRecords - safeRecordsCount,
            complianceRate: complianceRate,
            isCompliant: complianceRate >= 95.0,
            s501: {
                avgTemp: kpi501T.avg !== null ? kpi501T.avg : '-',
                minTemp: kpi501T.min !== null ? kpi501T.min : '-',
                maxTemp: kpi501T.max !== null ? kpi501T.max : '-',
                avgHum: kpi501H.avg !== null ? kpi501H.avg : '-',
                minHum: kpi501H.min !== null ? kpi501H.min : '-',
                maxHum: kpi501H.max !== null ? kpi501H.max : '-'
            },
            s502: {
                avgTemp: kpi502T.avg !== null ? kpi502T.avg : '-',
                minTemp: kpi502T.min !== null ? kpi502T.min : '-',
                maxTemp: kpi502T.max !== null ? kpi502T.max : '-',
                avgHum: kpi502H.avg !== null ? kpi502H.avg : '-',
                minHum: kpi502H.min !== null ? kpi502H.min : '-',
                maxHum: kpi502H.max !== null ? kpi502H.max : '-'
            }
        },
        hourly: { labels: hourlyLabels, t501: hourlyT501, h501: hourlyH501, t502: hourlyT502, h502: hourlyH502 },
        daily: { labels: dailyLabels, t501: dailyT501, h501: dailyH501, t502: dailyT502, h502: dailyH502, summaryRows: dailySummaryRows },
        weekly: { labels: weeklyLabels, t501: weeklyT501, h501: weeklyH501, t502: weeklyT502, h502: weeklyH502 },
        monthly: { labels: monthlyLabels, t501: monthlyT501, h501: monthlyH501, t502: monthlyT502, h502: monthlyH502 }
    };
}

/**
 * Main Export Execution: Generate Multi-Sheet Excel Workbook
 */
function exportExcelWithCharts(dt) {
    var btn = $('#btnExportExcelChart');
    var originalHtml = btn.html();

    // Ubah status tombol ke loading
    btn.prop('disabled', true).html('<i class="fe fe-loader fe-spin mr-1"></i> Menyusun 4 Grafik & File Excel...');

    setTimeout(function () {
        try {
            // 1. Ambil data mentah (Prioritaskan REPORT_DATA global dari PHP, fallback ke DataTables data)
            var rawData = [];
            if (typeof REPORT_DATA !== 'undefined' && Array.isArray(REPORT_DATA) && REPORT_DATA.length > 0) {
                rawData = REPORT_DATA;
            } else if (dt) {
                var dtRows = dt.rows({ search: 'applied' }).data();
                for (var rIdx = 0; rIdx < dtRows.length; rIdx++) {
                    rawData.push(dtRows[rIdx]);
                }
            }

            if (!rawData || rawData.length === 0) {
                alert('Tidak ada data yang tersedia untuk diekspor!');
                btn.prop('disabled', false).html(originalHtml);
                return;
            }

            // 2. Proses Agregasi 4 Granularitas
            var processed = processReportData(rawData);
            var thresholds = { tempLimit: TEMP_SAFE_LIMIT, humLimit: HUM_SAFE_LIMIT };

            // 3. Render 4 Grafik ke Canvas Base64 PNG
            // Dataset Template
            function getDatasets(t501, h501, t502, h502) {
                return [
                    { label: 'Suhu Folding (501)', data: t501, color: '#E67E22', axis: 'left', marker: 'circle' },
                    { label: 'Humid Folding (501)', data: h501, color: '#2980B9', axis: 'right', marker: 'square' },
                    { label: 'Suhu Dry Room (502)', data: t502, color: '#F39C12', axis: 'left', marker: 'triangle' },
                    { label: 'Humid Dry Room (502)', data: h502, color: '#16A085', axis: 'right', marker: 'diamond' }
                ];
            }

            var chart1Png = renderDualAxisChartCanvas(
                '1. GRAFIK TREN FLUKTUASI PER JAM (HOURLY RESOLUTION)',
                'Detail Pergerakan Suhu & Kelembapan Jam-demi-Jam | Batas Acuan: Suhu <= 30°C, Humid <= 60%',
                processed.hourly.labels,
                getDatasets(processed.hourly.t501, processed.hourly.h501, processed.hourly.t502, processed.hourly.h502),
                thresholds, 1200, 480
            );

            var chart2Png = renderDualAxisChartCanvas(
                '2. GRAFIK TREN RATA-RATA PER HARI (DAILY AGGREGATION)',
                'Evaluasi Fluktuasi Harian Rata-rata Lingkungan Kerja | Batas Acuan: Suhu <= 30°C, Humid <= 60%',
                processed.daily.labels,
                getDatasets(processed.daily.t501, processed.daily.h501, processed.daily.t502, processed.daily.h502),
                thresholds, 1200, 480
            );

            var chart3Png = renderDualAxisChartCanvas(
                '3. GRAFIK TREN RATA-RATA PER MINGGU (WEEKLY AGGREGATION)',
                'Tren Mingguan Evaluasi Pola Shift Operasional Pabrik | Batas Acuan: Suhu <= 30°C, Humid <= 60%',
                processed.weekly.labels,
                getDatasets(processed.weekly.t501, processed.weekly.h501, processed.weekly.t502, processed.weekly.h502),
                thresholds, 1200, 480
            );

            var chart4Png = renderDualAxisChartCanvas(
                '4. GRAFIK TREN RATA-RATA PER BULAN (MONTHLY AGGREGATION)',
                'Analisis Makro Pengaruh Pergantian Musim & Iklim Luar | Batas Acuan: Suhu <= 30°C, Humid <= 60%',
                processed.monthly.labels,
                getDatasets(processed.monthly.t501, processed.monthly.h501, processed.monthly.t502, processed.monthly.h502),
                thresholds, 1200, 480
            );

            // 4. Inisialisasi Workbook ExcelJS
            var wb = new ExcelJS.Workbook();
            wb.creator = 'PT Apparel One Indonesia - Enviro System';
            wb.lastModifiedBy = 'Enviro System AOI 5';
            wb.created = new Date();
            wb.modified = new Date();

            // ==================================================================
            // SHEET 1: DASHBOARD & GRAFIK (Executive Summary)
            // ==================================================================
            var s1 = wb.addWorksheet('Dashboard & Grafik', {
                views: [{ showGridLines: true }]
            });

            // Atur Lebar Kolom
            s1.columns = [
                { key: 'A', width: 4 },
                { key: 'B', width: 22 },
                { key: 'C', width: 20 },
                { key: 'D', width: 22 },
                { key: 'E', width: 20 },
                { key: 'F', width: 22 },
                { key: 'G', width: 20 },
                { key: 'H', width: 22 },
                { key: 'I', width: 20 }
            ];

            // Sisipkan Logo jika tersedia
            if (typeof AOI_LOGO_BASE64 !== 'undefined' && AOI_LOGO_BASE64.length > 50) {
                var logoId = wb.addImage({
                    base64: AOI_LOGO_BASE64,
                    extension: 'png'
                });
                s1.addImage(logoId, {
                    tl: { col: 1.1, row: 1.2 },
                    ext: { width: 140, height: 48 }
                });
            }

            // Header Teks Perusahaan
            s1.getCell('D2').value = 'PT APPAREL ONE INDONESIA (FACTORY 5)';
            s1.getCell('D2').font = { size: 16, bold: true, color: { argb: 'FF1E3A8A' } };

            s1.getCell('D3').value = 'EXECUTIVE SUMMARY & GRAFIK TREN TEMPERATURE & HUMIDITY';
            s1.getCell('D3').font = { size: 12, bold: true, color: { argb: 'FF334155' } };

            var filterRangeStr = (typeof REPORT_FILTER_INFO !== 'undefined' && REPORT_FILTER_INFO.range_text) ? REPORT_FILTER_INFO.range_text : 'Filter Aktif';
            s1.getCell('D4').value = 'Periode Laporan: ' + filterRangeStr + ' | Dicetak: ' + new Date().toLocaleString('id-ID') + ' WIB';
            s1.getCell('D4').font = { size: 9.5, italic: true, color: { argb: 'FF64748B' } };

            s1.getCell('D5').value = 'STANDAR BATAS ACUAN AMAN: SUHU <= ' + TEMP_SAFE_LIMIT.toFixed(1) + ' °C  |  HUMIDITY <= ' + HUM_SAFE_LIMIT.toFixed(1) + ' %';
            s1.getCell('D5').font = { size: 10, bold: true, color: { argb: 'FFDC2626' } };

            // Garis Pembatas Header
            s1.getRow(6).height = 10;

            // KARTU RINGKASAN KPI (Rows 7 s/d 10)
            function styleKpiCard(cellTitle, titleVal, cell1, label1, cell2, label2, cell3, label3, borderColor, bgColor) {
                s1.getCell(cellTitle).value = titleVal;
                s1.getCell(cellTitle).font = { bold: true, size: 11, color: { argb: 'FF1E293B' } };
                s1.getCell(cellTitle).alignment = { horizontal: 'center' };

                s1.getCell(cell1).value = label1;
                s1.getCell(cell1).font = { size: 9.5 };

                s1.getCell(cell2).value = label2;
                s1.getCell(cell2).font = { size: 9.5 };

                s1.getCell(cell3).value = label3;
                s1.getCell(cell3).font = { size: 9.5, bold: true };
            }

            // Card 1: FOLDING AREA (Cols B - C)
            s1.mergeCells('B7:C7');
            s1.mergeCells('B8:C8');
            s1.mergeCells('B9:C9');
            s1.mergeCells('B10:C10');
            styleKpiCard('B7', '1. FOLDING AREA (501)',
                'B8', 'Rata-rata Suhu : ' + processed.kpi.s501.avgTemp + ' °C (Min ' + processed.kpi.s501.minTemp + ' / Max ' + processed.kpi.s501.maxTemp + ')',
                'B9', 'Rata-rata Humid: ' + processed.kpi.s501.avgHum + ' % (Min ' + processed.kpi.s501.minHum + ' / Max ' + processed.kpi.s501.maxHum + ')',
                'B10', 'Batas Acuan : ' + (processed.kpi.s501.maxTemp <= TEMP_SAFE_LIMIT ? 'AMAN (Optimal)' : 'PERINGATAN (>30°C)')
            );
            ['B7', 'B8', 'B9', 'B10'].forEach(function(c) {
                s1.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF7ED' } };
            });

            // Card 2: DRY ROOM (Cols D - E)
            s1.mergeCells('D7:E7');
            s1.mergeCells('D8:E8');
            s1.mergeCells('D9:E9');
            s1.mergeCells('D10:E10');
            styleKpiCard('D7', '2. DRY ROOM (502)',
                'D8', 'Rata-rata Suhu : ' + processed.kpi.s502.avgTemp + ' °C (Min ' + processed.kpi.s502.minTemp + ' / Max ' + processed.kpi.s502.maxTemp + ')',
                'D9', 'Rata-rata Humid: ' + processed.kpi.s502.avgHum + ' % (Min ' + processed.kpi.s502.minHum + ' / Max ' + processed.kpi.s502.maxHum + ')',
                'D10', 'Batas Acuan : ' + (processed.kpi.s502.maxTemp <= TEMP_SAFE_LIMIT ? 'AMAN (Optimal)' : 'PERINGATAN (>30°C)')
            );
            ['D7', 'D8', 'D9', 'D10'].forEach(function(c) {
                s1.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDFA' } };
            });

            // Card 3: KEPATUHAN BATAS AMAN (Cols F - G)
            s1.mergeCells('F7:G7');
            s1.mergeCells('F8:G8');
            s1.mergeCells('F9:G9');
            s1.mergeCells('F10:G10');
            styleKpiCard('F7', '3. EVALUASI KEPATUHAN ACUAN',
                'F8', 'Batas Aman : Suhu <= ' + TEMP_SAFE_LIMIT + '°C | Hum <= ' + HUM_SAFE_LIMIT + '%',
                'F9', 'Tingkat Kepatuhan: ' + processed.kpi.complianceRate + ' %',
                'F10', 'Status Audit : ' + (processed.kpi.isCompliant ? 'MEMENUHI STANDAR' : 'TERDAPAT PELANGGARAN')
            );
            var compBg = processed.kpi.isCompliant ? 'FFF0FDF4' : 'FFFEF2F2';
            ['F7', 'F8', 'F9', 'F10'].forEach(function(c) {
                s1.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: compBg } };
            });

            // Card 4: SUMMARY SNAPSHOT (Cols H - I)
            s1.mergeCells('H7:I7');
            s1.mergeCells('H8:I8');
            s1.mergeCells('H9:I9');
            s1.mergeCells('H10:I10');
            styleKpiCard('H7', '4. TOTAL DATA RECORD',
                'H8', 'Total Snapshot : ' + processed.kpi.totalRecords + ' Jam Record',
                'H9', 'Data Sesuai Batas: ' + processed.kpi.safeRecordsCount + ' Jam',
                'H10', 'Pelanggaran Batas: ' + processed.kpi.exceedCount + ' Jam'
            );
            ['H7', 'H8', 'H9', 'H10'].forEach(function(c) {
                s1.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFEFF6FF' } };
            });

            // FUNGSI HELPER MENAMBAHKAN GAMBAR GRAFIK DENGAN HEADER SEKSI
            function addChartSection(rowHeader, headerText, chartPng, rowImgStart, rowImgEnd) {
                s1.mergeCells('B' + rowHeader + ':I' + rowHeader);
                var hCell = s1.getCell('B' + rowHeader);
                hCell.value = headerText;
                hCell.font = { bold: true, size: 11, color: { argb: 'FFFFFFFF' } };
                hCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
                hCell.alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
                s1.getRow(rowHeader).height = 24;

                var imgId = wb.addImage({
                    base64: chartPng,
                    extension: 'png'
                });
                s1.addImage(imgId, {
                    tl: { col: 1.1, row: rowImgStart - 0.8 },
                    ext: { width: 920, height: 360 }
                });

                // Ruang baris untuk gambar
                for (var r = rowImgStart; r <= rowImgEnd; r++) {
                    s1.getRow(r).height = 20;
                }
            }

            // 1. Grafik Per Jam (Rows 12 s/d 29)
            addChartSection(12, '1. GRAFIK TREN FLUKTUASI PER JAM (HOURLY)', chart1Png, 13, 29);

            // 2. Grafik Per Hari (Rows 31 s/d 48)
            addChartSection(31, '2. GRAFIK TREN RATA-RATA PER HARI (DAILY)', chart2Png, 32, 48);

            // 3. Grafik Per Minggu (Rows 50 s/d 67)
            addChartSection(50, '3. GRAFIK TREN RATA-RATA PER MINGGU (WEEKLY)', chart3Png, 51, 67);

            // 4. Grafik Per Bulan (Rows 69 s/d 86)
            addChartSection(69, '4. GRAFIK TREN RATA-RATA PER BULAN (MONTHLY)', chart4Png, 70, 86);

            // 5. Tabel Ringkasan Agregasi Harian (Row 89+)
            var curRow = 89;
            s1.mergeCells('B' + curRow + ':I' + curRow);
            var sumH = s1.getCell('B' + curRow);
            sumH.value = '5. TABEL RINGKASAN AGREGASI HARIAN & EVALUASI BATAS ACUAN';
            sumH.font = { bold: true, size: 11, color: { argb: 'FFFFFFFF' } };
            sumH.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F766E' } };
            sumH.alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
            s1.getRow(curRow).height = 24;

            curRow++;
            var tableHeaders = [
                'Tanggal', 'Hari', 'Avg Suhu 501', 'Min-Max 501', 'Avg Hum 501',
                'Avg Suhu 502', 'Min-Max 502', 'Avg Hum 502'
            ];
            var colLetters = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

            colLetters.forEach(function (col, idx) {
                var cell = s1.getCell(col + curRow);
                cell.value = tableHeaders[idx];
                cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF134E4A' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = {
                    top: { style: 'thin' }, left: { style: 'thin' },
                    bottom: { style: 'thin' }, right: { style: 'thin' }
                };
            });
            s1.getRow(curRow).height = 22;

            processed.daily.summaryRows.forEach(function (dRow) {
                curRow++;
                var rowVals = [
                    dRow.date, dRow.dayName,
                    dRow.avgT501 + ' °C', dRow.rangeT501, dRow.avgH501 + ' %',
                    dRow.avgT502 + ' °C', dRow.rangeT502, dRow.avgH502 + ' %'
                ];
                var isExceed = (dRow.status.indexOf('ALERT') !== -1);
                var rowBg = (curRow % 2 === 0) ? (isExceed ? 'FFFEF2F2' : 'FFF8FAFC') : (isExceed ? 'FFFEE2E2' : 'FFFFFFFF');

                colLetters.forEach(function (col, idx) {
                    var c = s1.getCell(col + curRow);
                    c.value = rowVals[idx];
                    c.font = { size: 9.5, bold: isExceed && (idx === 2 || idx === 5) };
                    c.alignment = { horizontal: (idx <= 1 ? 'center' : 'right'), vertical: 'middle' };
                    c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
                    c.border = {
                        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                    };
                });
                s1.getRow(curRow).height = 19;
            });

            // ==================================================================
            // SHEET 2: DATA DETAIL HISTORIS (Raw Data dengan Highlight Batas Aman)
            // ==================================================================
            var s2 = wb.addWorksheet('Data Detail Historis', {
                views: [{ showGridLines: true }]
            });

            s2.columns = [
                { header: 'No', key: 'no', width: 6 },
                { header: 'Factory', key: 'factory', width: 12 },
                { header: 'ID Location', key: 'id_loc', width: 14 },
                { header: 'Lokasi Area', key: 'loc_name', width: 18 },
                { header: 'Temperature (°C)', key: 'temp', width: 18 },
                { header: 'Humidity (%)', key: 'humidity', width: 16 },
                { header: 'Weather Status', key: 'weather', width: 16 },
                { header: 'Record Time', key: 'record_time', width: 22 },
                { header: 'Day Name', key: 'day_name', width: 14 }
            ];

            // Header Style Sheet 2
            var headerRow2 = s2.getRow(1);
            headerRow2.height = 26;
            headerRow2.eachCell(function (cell) {
                cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = {
                    top: { style: 'medium' }, left: { style: 'thin' },
                    bottom: { style: 'medium' }, right: { style: 'thin' }
                };
            });

            // Tambahkan Seluruh Baris Data
            for (var recIdx = 0; recIdx < processed.records.length; recIdx++) {
                var item = processed.records[recIdx];
                var isTempExceed = (item.temp !== null && item.temp > TEMP_SAFE_LIMIT);
                var isHumExceed = (item.humidity !== null && item.humidity > HUM_SAFE_LIMIT);

                var dataRow = s2.addRow({
                    no: recIdx + 1,
                    factory: 'AOI 5',
                    id_loc: item.id_location,
                    loc_name: item.location_name,
                    temp: item.temp !== null ? item.temp : 0.0,
                    humidity: item.humidity !== null ? item.humidity : 0.0,
                    weather: item.weather_status,
                    record_time: item.record_time,
                    day_name: item.day_name
                });

                dataRow.height = 20;
                var rowColor = (recIdx % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';

                // Format angka desimal
                dataRow.getCell('temp').numFmt = '0.0 "°C"';
                dataRow.getCell('humidity').numFmt = '0.0 "%"';

                // Alignment
                dataRow.getCell('no').alignment = { horizontal: 'center' };
                dataRow.getCell('factory').alignment = { horizontal: 'center' };
                dataRow.getCell('id_loc').alignment = { horizontal: 'center' };
                dataRow.getCell('loc_name').alignment = { horizontal: 'left' };
                dataRow.getCell('temp').alignment = { horizontal: 'right' };
                dataRow.getCell('humidity').alignment = { horizontal: 'right' };
                dataRow.getCell('weather').alignment = { horizontal: 'center' };
                dataRow.getCell('record_time').alignment = { horizontal: 'center' };
                dataRow.getCell('day_name').alignment = { horizontal: 'center' };

                // Borders
                dataRow.eachCell(function (cell) {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowColor } };
                    cell.border = {
                        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                    };
                });

                // Penandaan Khusus jika Suhu > 30°C
                if (isTempExceed) {
                    var tCell = dataRow.getCell('temp');
                    tCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
                    tCell.font = { bold: true, color: { argb: 'FF991B1B' } };
                }

                // Penandaan Khusus jika Kelembapan > 60%
                if (isHumExceed) {
                    var hCell = dataRow.getCell('humidity');
                    hCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
                    hCell.font = { bold: true, color: { argb: 'FF991B1B' } };
                }
            }

            // AutoFilter pada Sheet 2 (9 Kolom)
            s2.autoFilter = 'A1:I1';

            // 5. Generate Buffer dan Trigger Unduh File
            var dateSuffix = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            if (typeof REPORT_FILTER_INFO !== 'undefined' && REPORT_FILTER_INFO.range_text) {
                dateSuffix = REPORT_FILTER_INFO.range_text.replace(/[^a-zA-Z0-9]/g, '_');
            }
            var fileName = 'Laporan_Temp_Humid_AOI5_' + dateSuffix + '.xlsx';

            wb.xlsx.writeBuffer().then(function (buffer) {
                var blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);

                // Kembalikan tombol ke keadaan normal
                btn.prop('disabled', false).html(originalHtml);
            }).catch(function (writeErr) {
                console.error('[Export Excel Error]:', writeErr);
                alert('Gagal membuat file Excel: ' + writeErr.message);
                btn.prop('disabled', false).html(originalHtml);
            });

        } catch (err) {
            console.error('[Export Error]:', err);
            alert('Terjadi kesalahan saat memproses ekspor Excel: ' + err.message);
            btn.prop('disabled', false).html(originalHtml);
        }
    }, 150);
}

