@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    @if((int)Auth::user()->role === 1 && (int)Auth::user()->company === 1 && (int)Auth::user()->tariff !== 1)
        <div class="container mt-3">
            <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
                <div>
                    Your subscription is inactive. Unlock full analytics for your company.
                </div>
                <a href="{{ route('plans.index') }}" class="btn btn-sm btn-primary">Upgrade now</a>
            </div>
        </div>
    @endif
    @if(Auth::user()->role !== 0)
        <div class="dropdown-departments-modal-iTemperature">
            <div class="dropdown-departments-content-iTemperature" style="width: 30%; height: 60vh;"><br />
                <div class="dropdown-departments-content-text-iTemperature">
                    <span style="margin-left: 90%; margin-bottom: 10px; margin-top: -30px; cursor: pointer"><img class="close-dropdown" src="https://www.svgrepo.com/download/32011/close-button.svg" width="30px;"></span>
                    <div class="dropdown-departments-text-iTemperature" style="text-align: center">
                        <ul style="list-style: none;">
                            @if(Auth::user()->password !== null)
                                @foreach($departments as $department_title)
                                    @if(in_array($department_title->department, $exist_departments))
                                        <li style="text-align: left; padding-bottom: 10px"><a style="cursor:pointer;" department='{{$department_title->department}}' class="department-{{$department_title->id}}-iTemp modal-text-departments">{{$department_title->department}}</a></li>
                                        <script>
                                            $(".department-{{$department_title->id}}-iTemp").on("click", function(e)
                                            {
                                                e.preventDefault();

                                                var department = $(this).attr('department');

                                                function getResultsSatisfactionITemperature(canvas, dataResults) {
                                                    var getDataAboutUsers = [];

                                                    var commonDataKn = [0, 0];
                                                    var commonDataCl = [0, 0];
                                                    var commonDataTm = [0, 0];
                                                    var commonDataSk = [0, 0];
                                                    var commonDataMt = [0, 0];
                                                    var commonDataLd = [0, 0];
                                                    var commonDataOrg = [0, 0];
                                                    var commonDataSc = [0, 0];
                                                    var commonDataPj = [0, 0];
                                                    var commonDataCul = [0, 0];
                                                    var commonDataCh = [0, 0];

                                                    if (dataResults !== null && dataResults.length !== 0) {
                                                        var allValues = [];
                                                        dataResults.forEach(dataResult => {
                                                            allValues.push(JSON.parse(dataResult))
                                                        })

                                                        allValues.forEach(el => {
                                                            var mainDepartmentTitle = el["values"]["QID63_TEXT"].split(' ')[0];
                                                            var mainDepartmentTitleChecked = department.split(' ')[0];
                                                            if (mainDepartmentTitle === mainDepartmentTitleChecked || el["values"]["QID63_TEXT"].trim() === department.trim()) {
                                                                let knFirst = (el["values"]["QID1_2"] - el["values"]["QID1_1"] < 0 || isNaN(el["values"]["QID1_2"] - el["values"]["QID1_1"])) ? 1 : el["values"]["QID1_2"] - el["values"]["QID1_1"];
                                                                let clFirst = (el["values"]["QID2_2"] - el["values"]["QID2_1"] < 0 || isNaN(el["values"]["QID2_2"] - el["values"]["QID2_1"])) ? 1 : el["values"]["QID2_2"] - el["values"]["QID2_1"];
                                                                let tmFirst = (el["values"]["QID3_2"] - el["values"]["QID3_1"] < 0 || isNaN(el["values"]["QID3_2"] - el["values"]["QID3_1"])) ? 1 : el["values"]["QID3_2"] - el["values"]["QID3_1"];
                                                                let skFirst = (el["values"]["QID7_2"] - el["values"]["QID7_1"] < 0 || isNaN(el["values"]["QID7_2"] - el["values"]["QID7_1"])) ? 1 : el["values"]["QID7_2"] - el["values"]["QID7_1"];
                                                                let mtFirst = (el["values"]["QID8_2"] - el["values"]["QID8_1"] < 0 || isNaN(el["values"]["QID8_2"] - el["values"]["QID8_1"])) ? 1 : el["values"]["QID8_2"] - el["values"]["QID8_1"];
                                                                let ldFirst = (el["values"]["QID9_2"] - el["values"]["QID9_1"] < 0 || isNaN(el["values"]["QID9_2"] - el["values"]["QID9_1"])) ? 1 : el["values"]["QID9_2"] - el["values"]["QID9_1"];
                                                                let orgFirst = (el["values"]["QID10_2"] - el["values"]["QID10_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID10_2"] - el["values"]["QID10_1"];
                                                                let scFirst = (el["values"]["QID11_2"] - el["values"]["QID11_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID11_2"] - el["values"]["QID11_1"];
                                                                let pjFirst = el["values"]["QID30_1"];
                                                                let culFirst = el["values"]["QID31_1"];
                                                                let chFirst = el["values"]["QID32_1"];

                                                                let knSecond = (el["values"]["QID1_3"] - 4 < 0 || isNaN(el["values"]["QID1_3"] - 4)) ? 1 : el["values"]["QID1_3"] - 4;
                                                                let clSecond = (el["values"]["QID2_3"] - 4 < 0 || isNaN(el["values"]["QID2_3"] - 4)) ? 1 : el["values"]["QID2_3"] - 4;
                                                                let tmSecond = (el["values"]["QID3_3"] - 4 < 0 || isNaN(el["values"]["QID3_3"] - 4)) ? 1 : el["values"]["QID3_3"] - 4;
                                                                let skSecond = (el["values"]["QID7_3"] - 4 < 0 || isNaN(el["values"]["QID7_3"] - 4)) ? 1 : el["values"]["QID7_3"] - 4;
                                                                let mtSecond = (el["values"]["QID8_3"] - 4 < 0 || isNaN(el["values"]["QID8_3"] - 4)) ? 1 : el["values"]["QID8_3"] - 4;
                                                                let ldSecond = (el["values"]["QID9_3"] - 4 < 0 || isNaN(el["values"]["QID9_3"] - 4)) ? 1 : el["values"]["QID9_3"] - 4;
                                                                let orgSecond = (el["values"]["QID10_3"] - 4 < 0 || isNaN(el["values"]["QID10_3"] - 4)) ? 1 : el["values"]["QID10_3"] - 4;
                                                                let scSecond = (el["values"]["QID11_3"] - 4 < 0 || isNaN(el["values"]["QID11_3"] - 4)) ? 1 : el["values"]["QID11_3"] - 4;
                                                                let pjSecond = (el["values"]["QID30_3"] - 4 < 0 || isNaN(el["values"]["QID30_3"] - 4)) ? 1 : el["values"]["QID30_3"] - 4;
                                                                let culSecond = (el["values"]["QID31_3"] - 4 < 0 || isNaN(el["values"]["QID31_3"] - 4)) ? 1 : el["values"]["QID31_3"] - 4;
                                                                let chSecond = (el["values"]["QID32_3"] - 4 < 0 || isNaN(el["values"]["QID32_3"] - 4)) ? 1 : el["values"]["QID32_3"] - 4;

                                                                commonDataKn[0] = commonDataKn[0] + knFirst;
                                                                commonDataKn[1] = commonDataKn[1] + knSecond;

                                                                commonDataCl[0] = commonDataCl[0] + clFirst;
                                                                commonDataCl[1] = commonDataCl[1] + clSecond;

                                                                commonDataTm[0] = commonDataTm[0] + tmFirst;
                                                                commonDataTm[1] = commonDataTm[1] + tmSecond;

                                                                commonDataSk[0] = commonDataSk[0] + skFirst;
                                                                commonDataSk[1] = commonDataSk[1] + skSecond;

                                                                commonDataMt[0] = commonDataMt[0] + mtFirst;
                                                                commonDataMt[1] = commonDataMt[1] + mtSecond;

                                                                commonDataLd[0] = commonDataLd[0] + ldFirst;
                                                                commonDataLd[1] = commonDataLd[1] + ldSecond;

                                                                commonDataOrg[0] = commonDataOrg[0] + orgFirst;
                                                                commonDataOrg[1] = commonDataOrg[1] + orgSecond;

                                                                commonDataSc[0] = commonDataSc[0] + scFirst;
                                                                commonDataSc[1] = commonDataSc[1] + scSecond;

                                                                commonDataPj[0] = commonDataPj[0] + pjFirst;
                                                                commonDataPj[1] = commonDataPj[1] + pjSecond;

                                                                commonDataCul[0] = commonDataCul[0] + culFirst;
                                                                commonDataCul[1] = commonDataCul[1] + culSecond;

                                                                commonDataCh[0] = commonDataCh[0] + chFirst;
                                                                commonDataCh[1] = commonDataCh[1] + chSecond;
                                                            }
                                                        })

                                                        function mainResult(f, s, name) {
                                                            let x = (f === null) ? 1 : f;
                                                            let y = (s === null) ? 1 : s;
                                                            getDataAboutUsers.push({x: x, y: y, r: 12, label: name})
                                                        }

                                                        mainResult(Math.round(commonDataKn[0]/dataResults.length), Math.round(commonDataKn[1]/dataResults.length), 'Knowledge Progress');
                                                        mainResult(Math.round(commonDataCl[0]/dataResults.length), Math.round(commonDataCl[1]/dataResults.length), 'Client Impact');
                                                        mainResult(Math.round(commonDataTm[0]/dataResults.length), Math.round(commonDataTm[1]/dataResults.length), 'Team Impact');
                                                        mainResult(Math.round(commonDataSk[0]/dataResults.length), Math.round(commonDataSk[1]/dataResults.length), 'Skill Progress');
                                                        mainResult(Math.round(commonDataMt[0]/dataResults.length), Math.round(commonDataMt[1]/dataResults.length), 'Material Progress - Pay & Benefits');
                                                        mainResult(Math.round(commonDataLd[0]/dataResults.length), Math.round(commonDataLd[1]/dataResults.length), 'Team & Leadership Ethics');
                                                        mainResult(Math.round(commonDataOrg[0]/dataResults.length), Math.round(commonDataOrg[1]/dataResults.length), 'Organization Impact');
                                                        mainResult(Math.round(commonDataSc[0]/dataResults.length), Math.round(commonDataSc[1]/dataResults.length), 'Societal Impact Size');
                                                        mainResult(Math.round(commonDataPj[0]/dataResults.length), Math.round(commonDataPj[1]/dataResults.length), 'Project Impact');
                                                        mainResult(Math.round(commonDataCul[0]/dataResults.length), Math.round(commonDataCul[1]/dataResults.length), 'Organization Culture');
                                                        mainResult(Math.round(commonDataCh[0]/dataResults.length), Math.round(commonDataCh[1]/dataResults.length), 'Character Culture');

                                                        satisfactionITemperature(canvas, getDataAboutUsers);
                                                    } else {
                                                        satisfactionITemperature(canvas, [{x: 1, y: 1, r: 12, label: 'None results'}]);
                                                    }
                                                }
                                                function satisfactionITemperature(canvas, data) {
                                                    if(Chart.getChart(canvas)) {
                                                        Chart.getChart(canvas).destroy();
                                                    }

                                                    const bubbleData = {
                                                        datasets: [
                                                            {
                                                                data: data,
                                                                backgroundColor: "white",
                                                                borderColor: "white",
                                                                hoverBackgroundColor: "black"
                                                            }
                                                        ]
                                                    };

                                                    bubbleData.datasets.forEach(function (dataset) {
                                                        dataset.data.forEach(function (point) {
                                                            if(point.x === 0) {
                                                                point.x = 1;
                                                            } else if(point.x === 10) {
                                                                point.x = 9
                                                            }

                                                            if(point.y === 0) {
                                                                point.y = 1;
                                                            } else if(point.y === 10) {
                                                                point.y = 9
                                                            }
                                                        })
                                                    })

                                                    let options = {
                                                        plugins: {
                                                            legend: {
                                                                display: false,
                                                                labels: {
                                                                    font: {
                                                                        size: 12
                                                                    }
                                                                }
                                                            },
                                                            tooltip: {
                                                                callbacks: {
                                                                    label: function(context) {
                                                                        return context.dataset.data[context.dataIndex].label;
                                                                    }
                                                                },
                                                                displayColors: false,
                                                                padding: 2,
                                                                bodyFont: {
                                                                    size: 10
                                                                }
                                                            }
                                                        },
                                                        responsive: false,
                                                        scales: {
                                                            x: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            },
                                                            y: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            }
                                                        }
                                                    };

                                                    new Chart(canvas, {
                                                        type: "bubble",
                                                        data: bubbleData,
                                                        options: options
                                                    })
                                                }
                                                function satisfactionITemperatureShow() {
                                                    const canvas = document.getElementById("satisfaction-depatment").getContext("2d");
                                                    getResultsSatisfactionITemperature(canvas, {!! json_encode($qualtrics->data) !!})
                                                }

                                                satisfactionITemperatureShow();

                                                $(".dropdown-departments-modal-iTemperature").css({"display": "none"})
                                            })
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown-teams-modal-iTemperature">
            <div class="dropdown-teams-content-iTemperature" style="width: 30%; height: 60vh;"><br />
                <div class="dropdown-teams-content-text-iTemperature">
                    <span style="margin-left: 90%; margin-bottom: 10px; margin-top: -30px; cursor: pointer"><img class="close-dropdown" src="https://www.svgrepo.com/download/32011/close-button.svg" width="30px;"></span>
                    <div class="dropdown-teams-text-iTemperature" style="text-align: center">
                        <ul style="list-style: none;">
                            @if(Auth::user()->password !== null)
                                @foreach($teamleads as $sv)
                                    @if(Auth::user()->role == 2)
                                        @if($sv->department === $department)
                                            <li style="text-align: left"><a style="cursor:pointer;" class="team-{{$sv->id}}-iTemp modal-text-departments">{{$sv->name}}</a></li>
                                            <script>
                                                $(".team-{{$sv->id}}-iTemp").on("click", function(e)
                                                {
                                                    e.preventDefault();

                                                    function getResultsSatisfactionITemperature(canvas, dataResults) {
                                                        var getDataAboutUsers = [];
                                                        var department = null;
                                                        var sv = "{{$sv->name}}"

                                                        var commonDataKn = [0, 0];
                                                        var commonDataCl = [0, 0];
                                                        var commonDataTm = [0, 0];
                                                        var commonDataSk = [0, 0];
                                                        var commonDataMt = [0, 0];
                                                        var commonDataLd = [0, 0];
                                                        var commonDataOrg = [0, 0];
                                                        var commonDataSc = [0, 0];
                                                        var commonDataPj = [0, 0];
                                                        var commonDataCul = [0, 0];
                                                        var commonDataCh = [0, 0];

                                                        if (dataResults !== null && dataResults.length !== 0) {
                                                            var allValues = [];
                                                            dataResults.forEach(dataResult => {
                                                                allValues.push(JSON.parse(dataResult))
                                                            })

                                                            allValues.forEach(el => {
                                                                if(el["values"]["QID103_TEXT"] !== undefined) {
                                                                    if (el["values"]["QID103_TEXT"].trim() == sv.trim()) {
                                                                        let knFirst = (el["values"]["QID1_2"] - el["values"]["QID1_1"] < 0 || isNaN(el["values"]["QID1_2"] - el["values"]["QID1_1"])) ? 1 : el["values"]["QID1_2"] - el["values"]["QID1_1"];
                                                                        let clFirst = (el["values"]["QID2_2"] - el["values"]["QID2_1"] < 0 || isNaN(el["values"]["QID2_2"] - el["values"]["QID2_1"])) ? 1 : el["values"]["QID2_2"] - el["values"]["QID2_1"];
                                                                        let tmFirst = (el["values"]["QID3_2"] - el["values"]["QID3_1"] < 0 || isNaN(el["values"]["QID3_2"] - el["values"]["QID3_1"])) ? 1 : el["values"]["QID3_2"] - el["values"]["QID3_1"];
                                                                        let skFirst = (el["values"]["QID7_2"] - el["values"]["QID7_1"] < 0 || isNaN(el["values"]["QID7_2"] - el["values"]["QID7_1"])) ? 1 : el["values"]["QID7_2"] - el["values"]["QID7_1"];
                                                                        let mtFirst = (el["values"]["QID8_2"] - el["values"]["QID8_1"] < 0 || isNaN(el["values"]["QID8_2"] - el["values"]["QID8_1"])) ? 1 : el["values"]["QID8_2"] - el["values"]["QID8_1"];
                                                                        let ldFirst = (el["values"]["QID9_2"] - el["values"]["QID9_1"] < 0 || isNaN(el["values"]["QID9_2"] - el["values"]["QID9_1"])) ? 1 : el["values"]["QID9_2"] - el["values"]["QID9_1"];
                                                                        let orgFirst = (el["values"]["QID10_2"] - el["values"]["QID10_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID10_2"] - el["values"]["QID10_1"];
                                                                        let scFirst = (el["values"]["QID11_2"] - el["values"]["QID11_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID11_2"] - el["values"]["QID11_1"];
                                                                        let pjFirst = el["values"]["QID30_1"];
                                                                        let culFirst = el["values"]["QID31_1"];
                                                                        let chFirst = el["values"]["QID32_1"];

                                                                        let knSecond = (el["values"]["QID1_3"] - 4 < 0 || isNaN(el["values"]["QID1_3"] - 4)) ? 1 : el["values"]["QID1_3"] - 4;
                                                                        let clSecond = (el["values"]["QID2_3"] - 4 < 0 || isNaN(el["values"]["QID2_3"] - 4)) ? 1 : el["values"]["QID2_3"] - 4;
                                                                        let tmSecond = (el["values"]["QID3_3"] - 4 < 0 || isNaN(el["values"]["QID3_3"] - 4)) ? 1 : el["values"]["QID3_3"] - 4;
                                                                        let skSecond = (el["values"]["QID7_3"] - 4 < 0 || isNaN(el["values"]["QID7_3"] - 4)) ? 1 : el["values"]["QID7_3"] - 4;
                                                                        let mtSecond = (el["values"]["QID8_3"] - 4 < 0 || isNaN(el["values"]["QID8_3"] - 4)) ? 1 : el["values"]["QID8_3"] - 4;
                                                                        let ldSecond = (el["values"]["QID9_3"] - 4 < 0 || isNaN(el["values"]["QID9_3"] - 4)) ? 1 : el["values"]["QID9_3"] - 4;
                                                                        let orgSecond = (el["values"]["QID10_3"] - 4 < 0 || isNaN(el["values"]["QID10_3"] - 4)) ? 1 : el["values"]["QID10_3"] - 4;
                                                                        let scSecond = (el["values"]["QID11_3"] - 4 < 0 || isNaN(el["values"]["QID11_3"] - 4)) ? 1 : el["values"]["QID11_3"] - 4;
                                                                        let pjSecond = (el["values"]["QID30_3"] - 4 < 0 || isNaN(el["values"]["QID30_3"] - 4)) ? 1 : el["values"]["QID30_3"] - 4;
                                                                        let culSecond = (el["values"]["QID31_3"] - 4 < 0 || isNaN(el["values"]["QID31_3"] - 4)) ? 1 : el["values"]["QID31_3"] - 4;
                                                                        let chSecond = (el["values"]["QID32_3"] - 4 < 0 || isNaN(el["values"]["QID32_3"] - 4)) ? 1 : el["values"]["QID32_3"] - 4;

                                                                        commonDataKn[0] = commonDataKn[0] + knFirst;
                                                                        commonDataKn[1] = commonDataKn[1] + knSecond;

                                                                        commonDataCl[0] = commonDataCl[0] + clFirst;
                                                                        commonDataCl[1] = commonDataCl[1] + clSecond;

                                                                        commonDataTm[0] = commonDataTm[0] + tmFirst;
                                                                        commonDataTm[1] = commonDataTm[1] + tmSecond;

                                                                        commonDataSk[0] = commonDataSk[0] + skFirst;
                                                                        commonDataSk[1] = commonDataSk[1] + skSecond;

                                                                        commonDataMt[0] = commonDataMt[0] + mtFirst;
                                                                        commonDataMt[1] = commonDataMt[1] + mtSecond;

                                                                        commonDataLd[0] = commonDataLd[0] + ldFirst;
                                                                        commonDataLd[1] = commonDataLd[1] + ldSecond;

                                                                        commonDataOrg[0] = commonDataOrg[0] + orgFirst;
                                                                        commonDataOrg[1] = commonDataOrg[1] + orgSecond;

                                                                        commonDataSc[0] = commonDataSc[0] + scFirst;
                                                                        commonDataSc[1] = commonDataSc[1] + scSecond;

                                                                        commonDataPj[0] = commonDataPj[0] + pjFirst;
                                                                        commonDataPj[1] = commonDataPj[1] + pjSecond;

                                                                        commonDataCul[0] = commonDataCul[0] + culFirst;
                                                                        commonDataCul[1] = commonDataCul[1] + culSecond;

                                                                        commonDataCh[0] = commonDataCh[0] + chFirst;
                                                                        commonDataCh[1] = commonDataCh[1] + chSecond;
                                                                    }
                                                                }
                                                            })

                                                            function mainResult(f, s, name) {
                                                                let x = (f === null) ? 1 : f;
                                                                let y = (s === null) ? 1 : s;
                                                                getDataAboutUsers.push({x: x, y: y, r: 12, label: name})
                                                            }

                                                            mainResult(Math.round(commonDataKn[0]/dataResults.length), Math.round(commonDataKn[1]/dataResults.length), 'Knowledge Progress');
                                                            mainResult(Math.round(commonDataCl[0]/dataResults.length), Math.round(commonDataCl[1]/dataResults.length), 'Client Impact');
                                                            mainResult(Math.round(commonDataTm[0]/dataResults.length), Math.round(commonDataTm[1]/dataResults.length), 'Team Impact');
                                                            mainResult(Math.round(commonDataSk[0]/dataResults.length), Math.round(commonDataSk[1]/dataResults.length), 'Skill Progress');
                                                            mainResult(Math.round(commonDataMt[0]/dataResults.length), Math.round(commonDataMt[1]/dataResults.length), 'Material Progress - Pay & Benefits');
                                                            mainResult(Math.round(commonDataLd[0]/dataResults.length), Math.round(commonDataLd[1]/dataResults.length), 'Team & Leadership Ethics');
                                                            mainResult(Math.round(commonDataOrg[0]/dataResults.length), Math.round(commonDataOrg[1]/dataResults.length), 'Organization Impact');
                                                            mainResult(Math.round(commonDataSc[0]/dataResults.length), Math.round(commonDataSc[1]/dataResults.length), 'Societal Impact Size');
                                                            mainResult(Math.round(commonDataPj[0]/dataResults.length), Math.round(commonDataPj[1]/dataResults.length), 'Project Impact');
                                                            mainResult(Math.round(commonDataCul[0]/dataResults.length), Math.round(commonDataCul[1]/dataResults.length), 'Organization Culture');
                                                            mainResult(Math.round(commonDataCh[0]/dataResults.length), Math.round(commonDataCh[1]/dataResults.length), 'Character Culture');

                                                            satisfactionITemperature(canvas, getDataAboutUsers);
                                                        } else {
                                                            satisfactionITemperature(canvas, [{x: 1, y: 1, r: 12, label: 'None results'}]);
                                                        }
                                                    }
                                                    function satisfactionITemperature(canvas, data) {
                                                        if(Chart.getChart(canvas)) {
                                                            Chart.getChart(canvas).destroy();
                                                        }

                                                        const bubbleData = {
                                                            datasets: [
                                                                {
                                                                    data: data,
                                                                    backgroundColor: "white",
                                                                    borderColor: "white",
                                                                    hoverBackgroundColor: "black"
                                                                }
                                                            ]
                                                        };

                                                        bubbleData.datasets.forEach(function (dataset) {
                                                            dataset.data.forEach(function (point) {
                                                                if(point.x === 0) {
                                                                    point.x = 1;
                                                                } else if(point.x === 10) {
                                                                    point.x = 9
                                                                }

                                                                if(point.y === 0) {
                                                                    point.y = 1;
                                                                } else if(point.y === 10) {
                                                                    point.y = 9
                                                                }
                                                            })
                                                        })

                                                        let options = {
                                                            plugins: {
                                                                legend: {
                                                                    display: false,
                                                                    labels: {
                                                                        font: {
                                                                            size: 12
                                                                        }
                                                                    }
                                                                },
                                                                tooltip: {
                                                                    callbacks: {
                                                                        label: function(context) {
                                                                            return context.dataset.data[context.dataIndex].label;
                                                                        }
                                                                    },
                                                                    displayColors: false,
                                                                    padding: 2,
                                                                    bodyFont: {
                                                                        size: 10
                                                                    }
                                                                }
                                                            },
                                                            responsive: false,
                                                            scales: {
                                                                x: {
                                                                    min: 0,
                                                                    max: 10,
                                                                    display: false
                                                                },
                                                                y: {
                                                                    min: 0,
                                                                    max: 10,
                                                                    display: false
                                                                }
                                                            }
                                                        };

                                                        new Chart(canvas, {
                                                            type: "bubble",
                                                            data: bubbleData,
                                                            options: options
                                                        })
                                                    }
                                                    function satisfactionITemperatureShow() {
                                                        const canvas = document.getElementById("satisfaction-team").getContext("2d");
                                                        getResultsSatisfactionITemperature(canvas, {!! json_encode($qualtrics->data) !!})
                                                    }

                                                    satisfactionITemperatureShow();

                                                    $(".dropdown-teams-modal-iTemperature").css({"display": "none"})
                                                })
                                            </script>
                                        @endif
                                    @elseif(Auth::user()->role == 1)
                                        <li style="text-align: left"><a style="cursor:pointer;" class="team-{{$sv->id}}-iTemp modal-text-departments">{{$sv->name}}</a></li>
                                        <script>
                                            $(".team-{{$sv->id}}-iTemp").on("click", function(e)
                                            {
                                                e.preventDefault();

                                                function getResultsSatisfactionITemperature(canvas, dataResults) {
                                                    var getDataAboutUsers = [];
                                                    var sv = "{{$sv->name}}"

                                                    var commonDataKn = [0, 0];
                                                    var commonDataCl = [0, 0];
                                                    var commonDataTm = [0, 0];
                                                    var commonDataSk = [0, 0];
                                                    var commonDataMt = [0, 0];
                                                    var commonDataLd = [0, 0];
                                                    var commonDataOrg = [0, 0];
                                                    var commonDataSc = [0, 0];
                                                    var commonDataPj = [0, 0];
                                                    var commonDataCul = [0, 0];
                                                    var commonDataCh = [0, 0];

                                                    if (dataResults !== null && dataResults.length !== 0) {
                                                        var allValues = [];
                                                        dataResults.forEach(dataResult => {
                                                            allValues.push(JSON.parse(dataResult))
                                                        })

                                                        allValues.forEach(el => {
                                                            if(el["values"]["QID103_TEXT"] !== undefined) {
                                                                if (el["values"]["QID103_TEXT"].trim() == sv.trim()) {
                                                                    let knFirst = (el["values"]["QID1_2"] - el["values"]["QID1_1"] < 0 || isNaN(el["values"]["QID1_2"] - el["values"]["QID1_1"])) ? 1 : el["values"]["QID1_2"] - el["values"]["QID1_1"];
                                                                    let clFirst = (el["values"]["QID2_2"] - el["values"]["QID2_1"] < 0 || isNaN(el["values"]["QID2_2"] - el["values"]["QID2_1"])) ? 1 : el["values"]["QID2_2"] - el["values"]["QID2_1"];
                                                                    let tmFirst = (el["values"]["QID3_2"] - el["values"]["QID3_1"] < 0 || isNaN(el["values"]["QID3_2"] - el["values"]["QID3_1"])) ? 1 : el["values"]["QID3_2"] - el["values"]["QID3_1"];
                                                                    let skFirst = (el["values"]["QID7_2"] - el["values"]["QID7_1"] < 0 || isNaN(el["values"]["QID7_2"] - el["values"]["QID7_1"])) ? 1 : el["values"]["QID7_2"] - el["values"]["QID7_1"];
                                                                    let mtFirst = (el["values"]["QID8_2"] - el["values"]["QID8_1"] < 0 || isNaN(el["values"]["QID8_2"] - el["values"]["QID8_1"])) ? 1 : el["values"]["QID8_2"] - el["values"]["QID8_1"];
                                                                    let ldFirst = (el["values"]["QID9_2"] - el["values"]["QID9_1"] < 0 || isNaN(el["values"]["QID9_2"] - el["values"]["QID9_1"])) ? 1 : el["values"]["QID9_2"] - el["values"]["QID9_1"];
                                                                    let orgFirst = (el["values"]["QID10_2"] - el["values"]["QID10_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID10_2"] - el["values"]["QID10_1"];
                                                                    let scFirst = (el["values"]["QID11_2"] - el["values"]["QID11_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID11_2"] - el["values"]["QID11_1"];
                                                                    let pjFirst = el["values"]["QID30_1"];
                                                                    let culFirst = el["values"]["QID31_1"];
                                                                    let chFirst = el["values"]["QID32_1"];

                                                                    let knSecond = (el["values"]["QID1_3"] - 4 < 0 || isNaN(el["values"]["QID1_3"] - 4)) ? 1 : el["values"]["QID1_3"] - 4;
                                                                    let clSecond = (el["values"]["QID2_3"] - 4 < 0 || isNaN(el["values"]["QID2_3"] - 4)) ? 1 : el["values"]["QID2_3"] - 4;
                                                                    let tmSecond = (el["values"]["QID3_3"] - 4 < 0 || isNaN(el["values"]["QID3_3"] - 4)) ? 1 : el["values"]["QID3_3"] - 4;
                                                                    let skSecond = (el["values"]["QID7_3"] - 4 < 0 || isNaN(el["values"]["QID7_3"] - 4)) ? 1 : el["values"]["QID7_3"] - 4;
                                                                    let mtSecond = (el["values"]["QID8_3"] - 4 < 0 || isNaN(el["values"]["QID8_3"] - 4)) ? 1 : el["values"]["QID8_3"] - 4;
                                                                    let ldSecond = (el["values"]["QID9_3"] - 4 < 0 || isNaN(el["values"]["QID9_3"] - 4)) ? 1 : el["values"]["QID9_3"] - 4;
                                                                    let orgSecond = (el["values"]["QID10_3"] - 4 < 0 || isNaN(el["values"]["QID10_3"] - 4)) ? 1 : el["values"]["QID10_3"] - 4;
                                                                    let scSecond = (el["values"]["QID11_3"] - 4 < 0 || isNaN(el["values"]["QID11_3"] - 4)) ? 1 : el["values"]["QID11_3"] - 4;
                                                                    let pjSecond = (el["values"]["QID30_3"] - 4 < 0 || isNaN(el["values"]["QID30_3"] - 4)) ? 1 : el["values"]["QID30_3"] - 4;
                                                                    let culSecond = (el["values"]["QID31_3"] - 4 < 0 || isNaN(el["values"]["QID31_3"] - 4)) ? 1 : el["values"]["QID31_3"] - 4;
                                                                    let chSecond = (el["values"]["QID32_3"] - 4 < 0 || isNaN(el["values"]["QID32_3"] - 4)) ? 1 : el["values"]["QID32_3"] - 4;

                                                                    commonDataKn[0] = commonDataKn[0] + knFirst;
                                                                    commonDataKn[1] = commonDataKn[1] + knSecond;

                                                                    commonDataCl[0] = commonDataCl[0] + clFirst;
                                                                    commonDataCl[1] = commonDataCl[1] + clSecond;

                                                                    commonDataTm[0] = commonDataTm[0] + tmFirst;
                                                                    commonDataTm[1] = commonDataTm[1] + tmSecond;

                                                                    commonDataSk[0] = commonDataSk[0] + skFirst;
                                                                    commonDataSk[1] = commonDataSk[1] + skSecond;

                                                                    commonDataMt[0] = commonDataMt[0] + mtFirst;
                                                                    commonDataMt[1] = commonDataMt[1] + mtSecond;

                                                                    commonDataLd[0] = commonDataLd[0] + ldFirst;
                                                                    commonDataLd[1] = commonDataLd[1] + ldSecond;

                                                                    commonDataOrg[0] = commonDataOrg[0] + orgFirst;
                                                                    commonDataOrg[1] = commonDataOrg[1] + orgSecond;

                                                                    commonDataSc[0] = commonDataSc[0] + scFirst;
                                                                    commonDataSc[1] = commonDataSc[1] + scSecond;

                                                                    commonDataPj[0] = commonDataPj[0] + pjFirst;
                                                                    commonDataPj[1] = commonDataPj[1] + pjSecond;

                                                                    commonDataCul[0] = commonDataCul[0] + culFirst;
                                                                    commonDataCul[1] = commonDataCul[1] + culSecond;

                                                                    commonDataCh[0] = commonDataCh[0] + chFirst;
                                                                    commonDataCh[1] = commonDataCh[1] + chSecond;
                                                                }
                                                            }
                                                        })

                                                        function mainResult(f, s, name) {
                                                            let x = (f === null) ? 1 : f;
                                                            let y = (s === null) ? 1 : s;
                                                            getDataAboutUsers.push({x: x, y: y, r: 12, label: name})
                                                        }

                                                        mainResult(Math.round(commonDataKn[0]/dataResults.length), Math.round(commonDataKn[1]/dataResults.length), 'Knowledge Progress');
                                                        mainResult(Math.round(commonDataCl[0]/dataResults.length), Math.round(commonDataCl[1]/dataResults.length), 'Client Impact');
                                                        mainResult(Math.round(commonDataTm[0]/dataResults.length), Math.round(commonDataTm[1]/dataResults.length), 'Team Impact');
                                                        mainResult(Math.round(commonDataSk[0]/dataResults.length), Math.round(commonDataSk[1]/dataResults.length), 'Skill Progress');
                                                        mainResult(Math.round(commonDataMt[0]/dataResults.length), Math.round(commonDataMt[1]/dataResults.length), 'Material Progress - Pay & Benefits');
                                                        mainResult(Math.round(commonDataLd[0]/dataResults.length), Math.round(commonDataLd[1]/dataResults.length), 'Team & Leadership Ethics');
                                                        mainResult(Math.round(commonDataOrg[0]/dataResults.length), Math.round(commonDataOrg[1]/dataResults.length), 'Organization Impact');
                                                        mainResult(Math.round(commonDataSc[0]/dataResults.length), Math.round(commonDataSc[1]/dataResults.length), 'Societal Impact Size');
                                                        mainResult(Math.round(commonDataPj[0]/dataResults.length), Math.round(commonDataPj[1]/dataResults.length), 'Project Impact');
                                                        mainResult(Math.round(commonDataCul[0]/dataResults.length), Math.round(commonDataCul[1]/dataResults.length), 'Organization Culture');
                                                        mainResult(Math.round(commonDataCh[0]/dataResults.length), Math.round(commonDataCh[1]/dataResults.length), 'Character Culture');

                                                        satisfactionITemperature(canvas, getDataAboutUsers);
                                                    } else {
                                                        satisfactionITemperature(canvas, [{x: 1, y: 1, r: 12, label: 'None results'}]);
                                                    }
                                                }
                                                function satisfactionITemperature(canvas, data) {
                                                    if(Chart.getChart(canvas)) {
                                                        Chart.getChart(canvas).destroy();
                                                    }

                                                    const bubbleData = {
                                                        datasets: [
                                                            {
                                                                data: data,
                                                                backgroundColor: "white",
                                                                borderColor: "white",
                                                                hoverBackgroundColor: "black"
                                                            }
                                                        ]
                                                    };

                                                    bubbleData.datasets.forEach(function (dataset) {
                                                        dataset.data.forEach(function (point) {
                                                            if(point.x === 0) {
                                                                point.x = 1;
                                                            } else if(point.x === 10) {
                                                                point.x = 9
                                                            }

                                                            if(point.y === 0) {
                                                                point.y = 1;
                                                            } else if(point.y === 10) {
                                                                point.y = 9
                                                            }
                                                        })
                                                    })

                                                    let options = {
                                                        plugins: {
                                                            legend: {
                                                                display: false,
                                                                labels: {
                                                                    font: {
                                                                        size: 12
                                                                    }
                                                                }
                                                            },
                                                            tooltip: {
                                                                callbacks: {
                                                                    label: function(context) {
                                                                        return context.dataset.data[context.dataIndex].label;
                                                                    }
                                                                },
                                                                displayColors: false,
                                                                padding: 2,
                                                                bodyFont: {
                                                                    size: 10
                                                                }
                                                            }
                                                        },
                                                        responsive: false,
                                                        scales: {
                                                            x: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            },
                                                            y: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            }
                                                        }
                                                    };

                                                    new Chart(canvas, {
                                                        type: "bubble",
                                                        data: bubbleData,
                                                        options: options
                                                    })
                                                }
                                                function satisfactionITemperatureShow() {
                                                    const canvas = document.getElementById("satisfaction-team").getContext("2d");
                                                    getResultsSatisfactionITemperature(canvas, {!! json_encode($qualtrics->data) !!})
                                                }

                                                satisfactionITemperatureShow();

                                                $(".dropdown-teams-modal-iTemperature").css({"display": "none"})
                                            })
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown-departments-modal">
            <div class="dropdown-departments-content" style="width: 30%; height: 60vh;"><br />
                <div class="dropdown-departments-content-text">
                    <span style="margin-left: 90%; margin-bottom: 10px; margin-top: -30px; cursor: pointer"><img class="close-dropdown" src="https://www.svgrepo.com/download/32011/close-button.svg" width="30px;"></span>
                    <div class="dropdown-departments-text" style="text-align: center">
                        <ul style="list-style: none;">
                            @if(Auth::user()->password !== null)
                                @foreach($departments as $department_title)
                                    @if(in_array($department_title->department, $exist_departments))
                                        <li style="text-align: left"><a style="cursor:pointer;" department="{{$department_title->department}}" class="department-{{$department_title->id}} modal-text-departments">{{$department_title->department}}</a></li>
                                        <script>
                                            $(".department-{{$department_title->id}}").on("click", function(e)
                                            {
                                                e.preventDefault();

                                                var department = $(this).attr('department');

                                                function getResultsSatisfactionIndicator(canvas, dataResults) {
                                                    var getDataAboutUsers = [];

                                                    if (dataResults !== null && dataResults.length !== 0) {
                                                        var allValues = [];
                                                        dataResults.forEach(dataResult => {
                                                            allValues.push(JSON.parse(dataResult))
                                                        })

                                                        allValues.forEach(el => {
                                                            var mainDepartmentTitle = el["values"]["QID63_TEXT"].split(' ')[0];
                                                            var mainDepartmentTitleChecked = department.split(' ')[0];
                                                            if(mainDepartmentTitle === mainDepartmentTitleChecked || el["values"]["QID63_TEXT"].trim() === department.trim()) {
                                                                var x = Math.round((el["values"]["QID3_1"] + el["values"]["QID4_1"] + el["values"]["QID12_1"] + el["values"]["QID55_1"] + el["values"]["QID60_1"] + el["values"]["QID54_1"]) / 6);
                                                                var y = Math.round((el["values"]["QID50_1"] + el["values"]["QID50_1"] + el["values"]["QID4_1"] + el["values"]["QID15_1"] + el["values"]["QID14_1"] + el["values"]["QID7_6"]) / 6);

                                                                if (!isNaN(x) && !isNaN(y) && el["values"]["QID62_TEXT"] !== undefined) {
                                                                    let obj = {x: x, y: y, r: 10, label: el["values"]["QID62_TEXT"]};
                                                                    getDataAboutUsers.push(obj);
                                                                }
                                                            }
                                                        })

                                                        if(getDataAboutUsers.length !== 0) {
                                                            satisfactionIndicator(canvas, getDataAboutUsers);
                                                        } else {
                                                            let obj = {x: 1, y: 1, r: 10, label: 'None results'};
                                                            satisfactionIndicator(canvas, [obj]);
                                                        }
                                                    } else {
                                                        var defaultData = [{ x: 1, y: 1, r: 10, label: "None results"}];
                                                        satisfactionIndicator(canvas, defaultData);
                                                    }
                                                }
                                                function satisfactionIndicator(canvas, data) {
                                                    if(Chart.getChart(canvas)) {
                                                        Chart.getChart(canvas).destroy();

                                                    }
                                                    const bubbleData = {
                                                        datasets: [{
                                                            data: data,
                                                            backgroundColor: 'black',
                                                            borderColor: 'black',
                                                        }]
                                                    };

                                                    bubbleData.datasets.forEach(function(dataset) {
                                                        dataset.data.forEach(function(point) {
                                                            if (point.y === 0) {
                                                                point.y = 1;
                                                            }

                                                            if (point.x === 0) {
                                                                point.x = 1;
                                                            }

                                                            if (point.y === 10) {
                                                                point.y = 9;
                                                            }

                                                            if (point.x === 10) {
                                                                point.x = 9;
                                                            }
                                                        });
                                                    });

                                                    var options = {
                                                        plugins: {
                                                            legend: {
                                                                display: false,
                                                            },
                                                            tooltip: {
                                                                callbacks: {
                                                                    label: function(context) {
                                                                        return context.dataset.data[context.dataIndex].label;
                                                                    }
                                                                },
                                                                displayColors: false
                                                            },
                                                        },
                                                        responsive: false,
                                                        scales: {
                                                            x: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            },
                                                            y: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            }
                                                        }
                                                    };

                                                    new Chart(canvas, {
                                                        type: 'bubble',
                                                        data: bubbleData,
                                                        options: options
                                                    });
                                                }
                                                function satisfactionIndicatorShow() {
                                                    const canvas = document.getElementById("bubble-department").getContext("2d");
                                                    getResultsSatisfactionIndicator(canvas, {!! json_encode($qualtrics->data) !!});
                                                }

                                                satisfactionIndicatorShow();

                                                $(".dropdown-departments-modal").css({"display": "none"})
                                            })
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown-teams-modal">
            <div class="dropdown-teams-content" style="width: 30%; height: 60vh;"><br />
                <div class="dropdown-teams-content-text">
                    <span style="margin-left: 90%; margin-bottom: 10px; margin-top: -30px; cursor: pointer"><img class="close-dropdown" src="https://www.svgrepo.com/download/32011/close-button.svg" width="30px;"></span>
                    <div class="dropdown-teams-text" style="text-align: center">
                        <ul style="list-style: none;">
                            @if(Auth::user()->password !== null)
                                @foreach($teamleads as $sv)
                                    @if(Auth::user()->role == 2)
                                        @if($sv->department === $department)
                                            <li style="text-align: left"><a style="cursor:pointer;" class="team-{{$sv->id}} modal-text-departments">{{$sv->name}}</a></li>
                                            <script>
                                                $(".team-{{$sv->id}}").on("click", function(e)
                                                {
                                                    e.preventDefault();

                                                    function getResultsSatisfactionIndicator(canvas, dataResults) {
                                                        var getDataAboutUsers = [];
                                                        var sv = "{{$sv->name}}";

                                                        if (dataResults !== null && dataResults.length !== 0) {
                                                            var allValues = [];
                                                            dataResults.forEach(dataResult => {
                                                                allValues.push(JSON.parse(dataResult))
                                                            })

                                                            allValues.forEach(el => {
                                                                if(el["values"]["QID103_TEXT"] !== undefined) {
                                                                    if (el["values"]["QID103_TEXT"].trim() == sv.trim()) {
                                                                        var x = Math.round((el["values"]["QID3_1"] + el["values"]["QID4_1"] + el["values"]["QID12_1"] + el["values"]["QID55_1"] + el["values"]["QID60_1"] + el["values"]["QID54_1"]) / 6);
                                                                        var y = Math.round((el["values"]["QID50_1"] + el["values"]["QID50_1"] + el["values"]["QID4_1"] + el["values"]["QID15_1"] + el["values"]["QID14_1"] + el["values"]["QID7_6"]) / 6);

                                                                        if (!isNaN(x) && !isNaN(y) && el["values"]["QID62_TEXT"] !== undefined) {
                                                                            let obj = {
                                                                                x: x,
                                                                                y: y,
                                                                                r: 10,
                                                                                label: el["values"]["QID62_TEXT"]
                                                                            };
                                                                            getDataAboutUsers.push(obj);
                                                                        }
                                                                    }
                                                                }
                                                            })

                                                            if(getDataAboutUsers.length !== 0) {
                                                                satisfactionIndicator(canvas, getDataAboutUsers);
                                                            } else {
                                                                let obj = {x: 1, y: 1, r: 10, label: 'None results'};
                                                                satisfactionIndicator(canvas, [obj]);
                                                            }
                                                        } else {
                                                            var defaultData = [{ x: 1, y: 1, r: 10, label: "None results"}];
                                                            satisfactionIndicator(canvas, defaultData);
                                                        }
                                                    }
                                                    function satisfactionIndicator(canvas, data) {
                                                        if(Chart.getChart(canvas)) {
                                                            Chart.getChart(canvas).destroy();

                                                        }
                                                        const bubbleData = {
                                                            datasets: [{
                                                                data: data,
                                                                backgroundColor: 'black',
                                                                borderColor: 'black',
                                                            }]
                                                        };

                                                        bubbleData.datasets.forEach(function(dataset) {
                                                            dataset.data.forEach(function(point) {
                                                                if (point.y === 0) {
                                                                    point.y = 1;
                                                                }

                                                                if (point.x === 0) {
                                                                    point.x = 1;
                                                                }

                                                                if (point.y === 10) {
                                                                    point.y = 9;
                                                                }

                                                                if (point.x === 10) {
                                                                    point.x = 9;
                                                                }
                                                            });
                                                        });

                                                        var options = {
                                                            plugins: {
                                                                legend: {
                                                                    display: false,
                                                                },
                                                                tooltip: {
                                                                    callbacks: {
                                                                        label: function(context) {
                                                                            return context.dataset.data[context.dataIndex].label;
                                                                        }
                                                                    },
                                                                    displayColors: false
                                                                },
                                                            },
                                                            responsive: false,
                                                            scales: {
                                                                x: {
                                                                    min: 0,
                                                                    max: 10,
                                                                    display: false
                                                                },
                                                                y: {
                                                                    min: 0,
                                                                    max: 10,
                                                                    display: false
                                                                }
                                                            }
                                                        };

                                                        new Chart(canvas, {
                                                            type: 'bubble',
                                                            data: bubbleData,
                                                            options: options
                                                        });
                                                    }
                                                    function satisfactionIndicatorShow() {
                                                        const canvas = document.getElementById("bubble-team").getContext("2d");
                                                        getResultsSatisfactionIndicator(canvas, {!! json_encode($qualtrics->data) !!});
                                                    }

                                                    satisfactionIndicatorShow();

                                                    $(".dropdown-teams-modal").css({"display": "none"});
                                                })
                                            </script>
                                        @endif
                                    @elseif(Auth::user()->role == 1)
                                        <li style="text-align: left"><a style="cursor:pointer;" class="team-{{$sv->id}} modal-text-departments">{{$sv->name}}</a></li>
                                        <script>
                                            $(".team-{{$sv->id}}").on("click", function(e)
                                            {
                                                e.preventDefault();

                                                function getResultsSatisfactionIndicator(canvas, dataResults) {
                                                    var getDataAboutUsers = [];
                                                    var sv = "{{$sv->name}}";

                                                    if (dataResults !== null && dataResults.length !== 0) {
                                                        var allValues = [];
                                                        dataResults.forEach(dataResult => {
                                                            allValues.push(JSON.parse(dataResult))
                                                        })

                                                        allValues.forEach(el => {
                                                            if(el["values"]["QID103_TEXT"] !== undefined) {
                                                                if (el["values"]["QID103_TEXT"].trim() == sv.trim()) {
                                                                    var x = Math.round((el["values"]["QID3_1"] + el["values"]["QID4_1"] + el["values"]["QID12_1"] + el["values"]["QID55_1"] + el["values"]["QID60_1"] + el["values"]["QID54_1"]) / 6);
                                                                    var y = Math.round((el["values"]["QID50_1"] + el["values"]["QID50_1"] + el["values"]["QID4_1"] + el["values"]["QID15_1"] + el["values"]["QID14_1"] + el["values"]["QID7_6"]) / 6);

                                                                    if (!isNaN(x) && !isNaN(y) && el["values"]["QID62_TEXT"] !== undefined) {
                                                                        let obj = {
                                                                            x: x,
                                                                            y: y,
                                                                            r: 10,
                                                                            label: el["values"]["QID62_TEXT"]
                                                                        };
                                                                        getDataAboutUsers.push(obj);
                                                                    }
                                                                }
                                                            }
                                                        })

                                                        if(getDataAboutUsers.length !== 0) {
                                                            satisfactionIndicator(canvas, getDataAboutUsers);
                                                        } else {
                                                            let obj = {x: 1, y: 1, r: 10, label: 'None results'};
                                                            satisfactionIndicator(canvas, [obj]);
                                                        }
                                                    } else {
                                                        var defaultData = [{ x: 1, y: 1, r: 10, label: "None results"}];
                                                        satisfactionIndicator(canvas, defaultData);
                                                    }
                                                }
                                                function satisfactionIndicator(canvas, data) {
                                                    if(Chart.getChart(canvas)) {
                                                        Chart.getChart(canvas).destroy();

                                                    }
                                                    const bubbleData = {
                                                        datasets: [{
                                                            data: data,
                                                            backgroundColor: 'black',
                                                            borderColor: 'black',
                                                        }]
                                                    };

                                                    bubbleData.datasets.forEach(function(dataset) {
                                                        dataset.data.forEach(function(point) {
                                                            if (point.y === 0) {
                                                                point.y = 1;
                                                            }

                                                            if (point.x === 0) {
                                                                point.x = 1;
                                                            }

                                                            if (point.y === 10) {
                                                                point.y = 9;
                                                            }

                                                            if (point.x === 10) {
                                                                point.x = 9;
                                                            }
                                                        });
                                                    });

                                                    var options = {
                                                        plugins: {
                                                            legend: {
                                                                display: false,
                                                            },
                                                            tooltip: {
                                                                callbacks: {
                                                                    label: function(context) {
                                                                        return context.dataset.data[context.dataIndex].label;
                                                                    }
                                                                },
                                                                displayColors: false
                                                            },
                                                        },
                                                        responsive: false,
                                                        scales: {
                                                            x: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            },
                                                            y: {
                                                                min: 0,
                                                                max: 10,
                                                                display: false
                                                            }
                                                        }
                                                    };

                                                    new Chart(canvas, {
                                                        type: 'bubble',
                                                        data: bubbleData,
                                                        options: options
                                                    });
                                                }
                                                function satisfactionIndicatorShow() {
                                                    const canvas = document.getElementById("bubble-team").getContext("2d");
                                                    getResultsSatisfactionIndicator(canvas, {!! json_encode($qualtrics->data) !!});
                                                }

                                                satisfactionIndicatorShow();

                                                $(".dropdown-teams-modal").css({"display": "none"});
                                            })
                                        </script>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(Auth::user()->password == 'user')
        <div id="modalChangePassword" class="modalChangePassword">
            <div class="modal-content"><br />
                <div class="modal-content-text">
                    <div class="modal-text">
                        <form class="first_step" action="/home/updatePassword/{{Auth::user()->email}}" method="POST">
                            @csrf
                            <label class="form-label">Company title: </label>
                            <input type="text" name="company_title" class="form-control company_title" placeholder="Min. 5 sybmols">
                            <br />
                            <label class="form-label">New password: </label>
                            <input type="password" name="new_password" class="form-control password" placeholder="Min. 8 sybmols">
                            <br />
                            <label class="form-label">Confirm password: </label>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" class="form-control confirm_password">
                            <br />
                            <br />
                            <button type="submit" class="modal-button btn btn-primary buttonForConfirm" disabled><span class="modal-btn">Confirm</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="home-main">
        <div class="home-container">
            @if(Auth::user()->tariff == 1 && Auth::user()->password !== 'user' && Auth::user()->company_title !== null)
                <header class="home-header">

                    <div class="modalTest">
                        <div class="container-modal-test">
                            <div class="main-content-test">
                                {{--                                <button id="btn-test-1" class="btn-test" data-path="form-popup1">Gap Report</button>--}}
                                {{--                                <button id="btn-test-2" class="btn-test" data-path="form-popup2">Temperature</button>--}}
                                {{--                                <button id="btn-test-3" class="btn-test" data-path="form-popup3">Indicator</button>--}}
                                {{--                                <button id="btn-test-4" class="btn-test" data-path="form-popup4">Teams</button>--}}
                            </div>
                        </div>

                        <div class="modals-test">
                            <div class="modal-overlay-test">
                                <div class="modal-test modal--1-test" data-target="form-popup1">
                                    <div class="modal-content-test-1">
                                        <button id="modal-exit-1" class="modal-test-exit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="bi bi-x" viewBox="0 0 16 16">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center">
                                            <canvas id="gapReport-modal" class="" style="width: 220px; height: 500px;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modals-test-2">
                            <div class="modal-overlay-test-2">
                                <div class="modal-test-2 modal--1-test-2" data-target="form-popup2">
                                    <div class="modal-content-test-2">
                                        <button id="modal-exit-2" class="modal-test-exit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                        <div class="modal-test-block-2">
                                            <div class="box-2">
                                                <div class="box2-btn-cards">
                                                    <button class="box2-btn-cards-switch companyBubble-modal" @if(Auth::user()->role== 2 || Auth::user()->role == 3) style="pointer-events: none; color: black; font-weight: 400;"  @else style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Company</button>
                                                    <button class="box2-btn-cards-switch departmentBubble-modal" @if(Auth::user()->role == 3 || Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;"  @elseif(Auth::user()->role== 2) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Department</button>
                                                    <button class="box2-btn-cards-switch teamsBubble-modal" @if(Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;" @elseif(Auth::user()->role == 3) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Teams</button>
                                                </div>
                                                <div class="box2-content">
                                                    <div class="box2-graph">
                                                        <canvas id="bubble-company-modal" class="bubble-company-modal" height=240 width=240 style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                                        <canvas id="bubble-department-modal" class="bubble-department-modal" height=240 width=240 style="display: @if(Auth::user()->role== 2) block @else none @endif;"></canvas>
                                                        <canvas id="bubble-team-modal" class="bubble-team-modal" height=240 width=240 style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                                    </div>
                                                    <div class="box2-degrees-1">
                                                        <svg width="240"
                                                             height="6"
                                                             viewBox="0 0 359 8"
                                                             style="position: relative; top: 22px; right: -76px;"
                                                             fill="none"
                                                             xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                                        </svg>
                                                        <div class="box2-degrees-titles-1">
                                                            <p class="box2-degrees-title-12">Needs Unmet</p>
                                                            <p class="box2-degrees-title-22">Needs Met</p>
                                                        </div>
                                                    </div>
                                                    <div class="box2-degrees-2">
                                                        <svg width="247" height="6"
                                                             viewBox="0 0 359 8"
                                                             style="position: relative; bottom: 18px; left: 55px"
                                                             fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                                        </svg>
                                                        <div class="box2-degrees-titles">
                                                            <p class="box2-degrees-title-1">Low Importance</p>
                                                            <p class="box2-degrees-title-2">High Importance</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modals-test-3">
                            <div class="modal-overlay-test-3">
                                <div class="modal-test-3 modal--1-test-3" data-target="form-popup3">
                                    <div class="modal-content-test-3">
                                        <button id="modal-exit-3" class="modal-test-exit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                        <div class="modal-test-block-3">
                                            <div class="box-3">
                                                <div class="box3-content">
                                                    <div style="height: 330px; width: 340px">
                                                        <canvas id="teamsChart-modal" class="teamsChart-modal" width=343 height=340></canvas>
                                                        <p class="box3-degrees-title-12">Team culture evaluation</p>
                                                        <p class="box3-degrees-title-2">Weighted Indicator Satisfaction</p>
                                                        <div class="box3-degrees-title-11">
                                                            <p>Low</p>
                                                            <p>High</p>
                                                        </div>
                                                        <div class="box3-degrees-title-22">
                                                            <p>Low</p>
                                                            <p>High</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modals-test-1">
                            <div class="modal-overlay-test-1">
                                <div class="modal-test-1 modal--1-test-1" data-target="form-popup4">
                                    <div class="modal-content-test-1">
                                        <button id="modal-exit-4" class="modal-test-exit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                            </svg>
                                        </button>
                                        <div class="modal-test-block-1">
                                            <div class="box-1">
{{--                                                <div class="box1-btn-cards">--}}
{{--                                                    <button class="box1-btn-cards-switch company-modal" @if(Auth::user()->role== 2 || Auth::user()->role == 3) style="pointer-events: none; color: black; font-weight: 400;" @else style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Company</button>--}}
{{--                                                    <button class="box1-btn-cards-switch department-modal" @if(Auth::user()->role == 3 || Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;" @elseif(Auth::user()->role== 2) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Department @if(Auth::user()->role == 1) @endif</button>--}}
{{--                                                    <button class="box1-btn-cards-switch teams-modal" @if(Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;"  @elseif(Auth::user()->role == 3) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Teams @if(Auth::user()->role == 1 || Auth::user()->role == 2) @endif</button>--}}
{{--                                                </div>--}}

                                                <div class="box1-content">
                                                    <div class="satisfaction">
                                                        <p class="box1-left-text">Needs Attentions</p>
                                                        <canvas id="satisfaction-company-modal" class="satisfaction-company-modal" width="810" height="180" style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                                        <canvas id="satisfaction-depatment-modal" class="satisfaction-depatment-modal" width="810" height="180" style="display: @if(Auth::user()->role== 2) block @else none @endif;"></canvas>
                                                        <canvas id="satisfaction-team-modal" class="satisfaction-team-modal" width="810" height="180" style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                                        <p class="box1-right-text">Doing Great</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="home-header-content">
                        <div class="home-header-cont-1">
                            <div class="home-h-title">Satisfaction ITemperature Index</div>
                            <button id="btn-test-4" data-path="form-popup4" class="btn-module-show-ITemperature">
                                <svg width="12"
                                     height="8"
                                     style="margin: 0 0 0 10px"
                                     viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path class="path-satisfaction" d="M11.1812 4.05851C11.3765 3.86325 11.3765 3.54667 11.1812 3.3514L7.99921 0.169423C7.80395 -0.0258394 7.48737 -0.0258395 7.2921 0.169423C7.09684 0.364685 7.09684 0.681267 7.2921 0.87653L10.1205 3.70496L7.2921 6.53338C7.09684 6.72865 7.09684 7.04523 7.2921 7.24049C7.48736 7.43575 7.80395 7.43575 7.99921 7.24049L11.1812 4.05851ZM0.827637 4.20496L10.8276 4.20496L10.8276 3.20496L0.827637 3.20496L0.827637 4.20496Z" fill="#3E3E3E"/>
                                </svg>
                            </button>
                            <div class="modal-satisfactionITemperatureIndex" style="height: 100vh">
                                <!-- Modal c+-ontent -->
                                <div style="display: flex; justify-content: center; align-items: center; height: 100vh">
                                    <div class="modal-content-1">
                                        <span class="close">&times;</span>
                                        <div class="modal-flex-content-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="home-header-cont-2">
                            <div class="home-h-title">Gap Report</div>
                            <button id="btn-test-1" data-path="form-popup1" class="btn-module-show-gapReport">
                                <svg width="12"
                                     height="8"
                                     style="margin: 0 0 0 10px"
                                     viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path class="path-satisfaction" d="M11.1812 4.05851C11.3765 3.86325 11.3765 3.54667 11.1812 3.3514L7.99921 0.169423C7.80395 -0.0258394 7.48737 -0.0258395 7.2921 0.169423C7.09684 0.364685 7.09684 0.681267 7.2921 0.87653L10.1205 3.70496L7.2921 6.53338C7.09684 6.72865 7.09684 7.04523 7.2921 7.24049C7.48736 7.43575 7.80395 7.43575 7.99921 7.24049L11.1812 4.05851ZM0.827637 4.20496L10.8276 4.20496L10.8276 3.20496L0.827637 3.20496L0.827637 4.20496Z" fill="#3E3E3E"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                </header>

                <main class="home-main-content">
                    <div class="container">

                        <div class="box-1">
                            <div class="box1-btn-cards">
                                <button @if(Auth::user()->role== 2 || Auth::user()->role == 3) class="box1-btn-cards-switch company"  disabled @else class="box1-btn-cards-switch active company" @endif>Company</button>
                                <button @if(Auth::user()->role == 3 || Auth::user()->role == 0) class="box1-btn-cards-switch department" disabled @elseif(Auth::user()->role== 2) class="box1-btn-cards-switch active department" @else class="box1-btn-cards-switch department" @endif>Department @if(Auth::user()->role == 1)
                                        <div class="loader-main loader-btn-support-department" style="display: none; background: transparent !important; border-radius: 0">
                                            <div class="spinner-border text-primary" role="status">
                                            </div>
                                        </div>
                                        <a class="departments-dropdown-iTemperature">▼</a>
                                    @endif</button>
                                <button @if(Auth::user()->role == 0) class="box1-btn-cards-switch teams"  @elseif(Auth::user()->role == 3) class="box1-btn-cards-switch active teams" @else class="box1-btn-cards-switch teams" @endif>Teams @if(Auth::user()->role == 1 || Auth::user()->role == 2)
                                        <div class="loader-main loader-btn-support-teams" style="display: none; background: transparent !important; border-radius: 0">
                                            <div class="spinner-border text-primary" role="status">
                                            </div>
                                        </div>
                                        <a class="teams-dropdown-iTemperature">▼</a>
                                    @endif</button>
                            </div>

                            <div class="box1-content">
                                <div id="loaderITemperature" class="loader-main">
                                    <div class="spinner-border text-primary" role="status">
                                    </div>
                                </div>
                                <div class="satisfaction">
                                    <p class="box1-left-text">Needs Attentions</p>
                                    <canvas id="satisfaction-company" class="satisfaction-company" width="810" height="180" style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                    <canvas id="satisfaction-depatment" class="satisfaction-depatment" width="810" height="180" style="display: @if(Auth::user()->role== 2) block @else none @endif;"></canvas>
                                    <canvas id="satisfaction-team" class="satisfaction-team" width="810" height="180" style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                    <p class="box1-right-text">Doing Great</p>
                                </div>
                            </div>
                        </div>

                        <div class="modal-satisfactionITemperatureIndex" style="height: 100%;">
                            <!-- Modal c+-ontent -->
                            <div style="height: 100%; display: flex; justify-content: center; align-items: center">
                                <div class="modal-content-1">
                                    <span class="close">&times;</span>
                                    <div class="modal-flex-content-1" >
                                        <div class="box-1">
                                            <div class="box1-btn-cards">
                                                <button class="box1-btn-cards-switch company-modal" @if(Auth::user()->role== 2 || Auth::user()->role == 3) style="pointer-events: none; color: black; font-weight: 400;" @else style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Company</button>
                                                <button class="box1-btn-cards-switch department-modal" @if(Auth::user()->role == 3 || Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;" @elseif(Auth::user()->role== 2) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Department @if(Auth::user()->role == 1) @endif</button>
                                                <button class="box1-btn-cards-switch teams-modal" @if(Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;"  @elseif(Auth::user()->role == 3) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Teams @if(Auth::user()->role == 1 || Auth::user()->role == 2) @endif</button>
                                            </div>

                                            <div class="box1-content">
                                                <div class="satisfaction">
                                                    <p class="box1-left-text">Needs Attentions</p>
                                                    <canvas id="satisfaction-company-modal" class="satisfaction-company-modal" width="810" height="180" style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                                    <canvas id="satisfaction-depatment-modal" class="satisfaction-depatment-modal" width="810" height="180" style="display: @if(Auth::user()->role== 2) block @else none @endif;"></canvas>
                                                    <canvas id="satisfaction-team-modal" class="satisfaction-team-modal" width="810" height="180" style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                                    <p class="box1-right-text">Doing Great</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="box-2">
                            <div class="box2-title">
                                <p style="margin: 0px">Satisfaction Indicator Report</p>
                                <button id="btn-test-2" data-path="form-popup2" class="btn-module-show-Indicator">
                                    <svg width="12"
                                         height="8"
                                         style="margin: 0 0 0 10px"
                                         viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path class="path-satisfaction" d="M11.1812 4.05851C11.3765 3.86325 11.3765 3.54667 11.1812 3.3514L7.99921 0.169423C7.80395 -0.0258394 7.48737 -0.0258395 7.2921 0.169423C7.09684 0.364685 7.09684 0.681267 7.2921 0.87653L10.1205 3.70496L7.2921 6.53338C7.09684 6.72865 7.09684 7.04523 7.2921 7.24049C7.48736 7.43575 7.80395 7.43575 7.99921 7.24049L11.1812 4.05851ZM0.827637 4.20496L10.8276 4.20496L10.8276 3.20496L0.827637 3.20496L0.827637 4.20496Z" fill="#3E3E3E"/>
                                    </svg>
                                </button>

                                <div class="modal-satisfactionIndicatorReport">
                                    <!-- Modal c+-ontent -->
                                    <div style="height: 100%; display: flex; justify-content: center; align-items: center">
                                        <div class="modal-content-2">
                                            <span class="close">&times;</span>
                                            <div class="modal-flex-content-2" >
                                                <div class="box-2" style="position: relative; top: 170px; transform: scale(1.7);">
                                                    <div class="box2-btn-cards">
                                                        <button class="box2-btn-cards-switch companyBubble-modal" @if(Auth::user()->role== 2 || Auth::user()->role == 3) style="pointer-events: none; color: black; font-weight: 400;"  @else style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Company</button>
                                                        <button class="box2-btn-cards-switch departmentBubble-modal" @if(Auth::user()->role == 3 || Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;"  @elseif(Auth::user()->role== 2) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Department</button>
                                                        <button class="box2-btn-cards-switch teamsBubble-modal" @if(Auth::user()->role == 0) style="pointer-events: none; color: black; font-weight: 400;" @elseif(Auth::user()->role == 3) style="background-color: #ffff; color: black; font-weight: 700; border: 1px solid #D1D1D1; height: 60px;" @endif>Teams</button>
                                                    </div>
                                                    <div class="box2-content">
                                                        <div class="box2-graph">
                                                            <canvas id="bubble-company-modal" class="bubble-company-modal" height=240 width=240 style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                                            <canvas id="bubble-department-modal" class="bubble-department-modal" height=240 width=240 style="display: @if(Auth::user()->role== 2) block @else none @endif;"></canvas>
                                                            <canvas id="bubble-team-modal" class="bubble-team-modal" height=240 width=240 style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                                        </div>
                                                        <div class="box2-degrees-1">
                                                            <svg width="240"
                                                                 height="6"
                                                                 viewBox="0 0 359 8"
                                                                 style="position: relative; top: 22px; right: -76px;"
                                                                 fill="none"
                                                                 xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                                            </svg>
                                                            <div class="box2-degrees-titles-1">
                                                                <p class="box2-degrees-title-12">Needs Unmet</p>
                                                                <p class="box2-degrees-title-22">Needs Met</p>
                                                            </div>
                                                        </div>
                                                        <div class="box2-degrees-2">
                                                            <svg width="247" height="6"
                                                                 viewBox="0 0 359 8"
                                                                 style="position: relative; bottom: 18px; left: 55px"
                                                                 fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                                            </svg>
                                                            <div class="box2-degrees-titles">
                                                                <p class="box2-degrees-title-1">Low Importance</p>
                                                                <p class="box2-degrees-title-2">High Importance</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="box2-btn-cards">
                                <button @if(Auth::user()->role == 2 || Auth::user()->role == 3) class="box2-btn-cards-switch companyBubble" disabled @else class="box2-btn-cards-switch active companyBubble" @endif>Company</button>
                                <button @if(Auth::user()->role == 3 || Auth::user()->role == 0) class="box2-btn-cards-switch departmentBubble" disabled @elseif(Auth::user()->role== 2) class="box2-btn-cards-switch active departmentBubble" @else class="box2-btn-cards-switch departmentBubble" @endif>Department @if(Auth::user()->role == 1)
                                        <div class="loader-main loader-btn-support-departmentBubble" style="display: none; background: transparent !important; border-radius: 0">
                                            <div class="spinner-border text-primary" role="status">
                                            </div>
                                        </div>
                                        <a class="departments-dropdown">▼</a>
                                    @endif</button>
                                <button @if(Auth::user()->role == 0) class="box2-btn-cards-switch teamsBubble"  @elseif(Auth::user()->role == 3) class="box2-btn-cards-switch active teamsBubble" @else class="box2-btn-cards-switch teamsBubble" @endif>Teams @if(Auth::user()->role == 1 || Auth::user()->role == 2)
                                        <div class="loader-main loader-btn-support-teamsBubble" style="display: none; background: transparent !important; border-radius: 0">
                                            <div class="spinner-border text-primary" role="status">
                                            </div>
                                        </div>
                                        <a class="teams-dropdown">▼</a>
                                    @endif</button>
                            </div>
                            <div class="box2-content">
                                <div id="loaderIndicator" class="loader-main">
                                    <div class="spinner-border text-primary" role="status">
                                    </div>
                                </div>
                                <div class="box2-graph">
                                    <canvas id="bubble-company" class="bubble-company bubbleChart" height=240 width=240 style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 3) none @else block @endif;"></canvas>
                                    <canvas id="bubble-department" class="bubble-department bubbleChart" height=240 width=240 style="display: @if(Auth::user()->role== 2 || Auth::user()->role == 1) block @else none @endif;"></canvas>
                                    <canvas id="bubble-team" class="bubble-team bubbleChart" height=240 width=240 style="display: @if(Auth::user()->role == 3) block @else none @endif;"></canvas>
                                </div>
                                <div class="box2-degrees-1">
                                    <svg width="240"
                                         height="6"
                                         viewBox="0 0 359 8"
                                         style="position: relative; top: 22px; right: -76px;"
                                         fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                    </svg>
                                    <div class="box2-degrees-titles-1">
                                        <p class="box2-degrees-title-12">Needs Unmet</p>
                                        <p class="box2-degrees-title-22">Needs Met</p>
                                    </div>
                                </div>
                                <div class="box2-degrees-2">
                                    <svg width="247" height="6"
                                         viewBox="0 0 359 8"
                                         style="position: relative; bottom: 18px; left: 55px"
                                         fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1.03561 3.34579C0.840345 3.54105 0.840345 3.85763 1.03561 4.05289L4.21759 7.23487C4.41285 7.43014 4.72943 7.43014 4.92469 7.23487C5.11996 7.03961 5.11996 6.72303 4.92469 6.52777L2.09627 3.69934L4.92469 0.870914C5.11996 0.675652 5.11996 0.359069 4.92469 0.163807C4.72943 -0.0314553 4.41285 -0.0314553 4.21759 0.163807L1.03561 3.34579ZM358.036 4.05289C358.231 3.85763 358.231 3.54105 358.036 3.34579L354.854 0.163807C354.659 -0.0314553 354.342 -0.0314553 354.147 0.163807C353.952 0.359069 353.952 0.675652 354.147 0.870914L356.975 3.69934L354.147 6.52777C353.952 6.72303 353.952 7.03961 354.147 7.23487C354.342 7.43014 354.659 7.43014 354.854 7.23487L358.036 4.05289ZM1.38916 4.19934L357.682 4.19934V3.19934L1.38916 3.19934V4.19934Z" fill="black"/>
                                    </svg>
                                    <div class="box2-degrees-titles">
                                        <p class="box2-degrees-title-1">Low Importance</p>
                                        <p class="box2-degrees-title-2">High Importance</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="box-3">
                            <div class="box3-title">
                                <p style="margin: 0">Teams chart</p>
                                <button id="btn-test-3" data-path="form-popup3" class="btn-module-show-team">
                                    <svg width="12"
                                         height="8"
                                         style="margin: 0 0 0 10px"
                                         viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path class="path-satisfaction" d="M11.1812 4.05851C11.3765 3.86325 11.3765 3.54667 11.1812 3.3514L7.99921 0.169423C7.80395 -0.0258394 7.48737 -0.0258395 7.2921 0.169423C7.09684 0.364685 7.09684 0.681267 7.2921 0.87653L10.1205 3.70496L7.2921 6.53338C7.09684 6.72865 7.09684 7.04523 7.2921 7.24049C7.48736 7.43575 7.80395 7.43575 7.99921 7.24049L11.1812 4.05851ZM0.827637 4.20496L10.8276 4.20496L10.8276 3.20496L0.827637 3.20496L0.827637 4.20496Z" fill="#3E3E3E"/>
                                    </svg>
                                </button>

                                <div class="modal-team" style="height: 100%;">
                                    <!-- Modal c+-ontent -->
                                    <div style="height: 100%; display: flex; justify-content: center; align-items: center">
                                        <div class="modal-content-2">
                                            <span class="close">&times;</span>
                                            <div class="modal-flex-content-3">
                                                <div class="box3-content">
                                                    <div style="height: 330px; width: 340px">
                                                        <canvas id="teamsChart-modal" class="teamsChart-modal" width=343 height=340></canvas>
                                                        <p class="box3-degrees-title-12">Team culture evaluation</p>
                                                        <p class="box3-degrees-title-2">Weighted Indicator Satisfaction</p>
                                                        <div class="box3-degrees-title-11">
                                                            <p>Low</p>
                                                            <p>High</p>
                                                        </div>
                                                        <div class="box3-degrees-title-22">
                                                            <p>Low</p>
                                                            <p>High</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="box3-content">
                                <div id="loaderTeam" class="loader-main">
                                    <div class="spinner-border text-primary" role="status">
                                    </div>
                                </div>
                                <div style="height: 330px; width: 340px">
                                    <canvas id="teamsChart" width=343 height=340></canvas>
                                    <p class="box3-degrees-title-12">Team culture evaluation</p>
                                    <p class="box3-degrees-title-2">Weighted Indicator Satisfaction</p>
                                    <div class="box3-degrees-title-11">
                                        <p>Low</p>
                                        <p>High</p>
                                    </div>
                                    <div class="box3-degrees-title-22">
                                        <p>Low</p>
                                        <p>High</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="box-4" class="box-4 d-flex align-items-center justify-content-center">
                            <div id="loaderGapReport" class="loader-main">
                                <div class="spinner-border text-primary" role="status">
                                </div>
                            </div>
                            <canvas id="gapReport" width="300px" height="700px" style="transform: scale(0.9);"></canvas></div>
                        {{--                            <div>--}}
                        {{--                                <div class="panel-report 0">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 10%; background: rgba(187, 187, 187, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(94, 94, 94); width: 10%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(191, 191, 191) -5.46%, rgba(206, 206, 206, 0.373) 77.74%, rgba(241, 241, 241, 0) 127.35%); width: 30%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Team Impact</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 1">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 0%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 0%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Team &amp; Leadership Ethics</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 2">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 0%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 0%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Societal Impact Size</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 3">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 20%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 20%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 20%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Client Impact</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 4">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 20%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 20%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 20%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Material Progress - Pay &amp; Benefits</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 5">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 10%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 10%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Knowledge Progress</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 6">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 40%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 40%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 20%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Organization Impact</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 7">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 50%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 50%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Organization Culture</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 8">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 70%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 70%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Project Impact</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 9">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 70%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 70%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Character Culture</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}

                        {{--                                <div class="panel-report 10">--}}
                        {{--                                    <div class="progress-report">--}}
                        {{--                                        <div id="target" class="target" style="width: 90%; background: rgba(0, 226, 255, 0.1);"></div>--}}
                        {{--                                        <div id="limit" class="limit" style="border-right: 2px solid rgb(10, 136, 153); width: 90%;"></div>--}}
                        {{--                                        <div id="progress-done" class="progress-done" style="background: linear-gradient(269.49deg, rgb(99, 228, 244) -19.19%, rgb(124, 222, 235) 9.81%, rgb(197, 247, 252) 61.71%, rgb(240, 254, 255) 127.35%); width: 10%;"></div>--}}
                        {{--                                    </div>--}}
                        {{--                                    <div class="panel-report-1">--}}
                        {{--                                        <div class="panel-report-degrees">0</div>--}}
                        {{--                                        <div class="panel-report-info">--}}
                        {{--                                            <div class="panel-report-name">Skill Progress</div>--}}
                        {{--                                            <div class="panel-report-rates">0</div>--}}
                        {{--                                        </div>--}}
                        {{--                                    </div>--}}
                        {{--                                </div>--}}
                        {{--                            </div>--}}
                        </canvas>

                    </div>
                    <div class="modal-gapReport" style="display: none; overflow-y: hidden;">
                        <div class="modal-gapReport-content">
                            <div class="modal-content-4" style=" transform: scale(1.0);">
                                <span class="close" style="margin-top: -4px;">&times;</span>
                                <div class="modal-content-flex-4" style="margin-top: 90px; transform: scale(1.2); align-items: center; display: flex; justify-content: center;">

                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            @else
                <!-- -->
            @endif
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script type="text/javascript">
        function unBlockCharts() {
            $('#loaderIndicator') !== null ? $('#loaderIndicator').fadeOut() : false;
            $('#loaderTeam') !== null ? $('#loaderTeam').fadeOut() : false;
            $('#loaderITemperature') !== null ? $('#loaderITemperature').fadeOut() : false;
            $('#loaderGapReport') !== null ? $('#loaderGapReport').fadeOut() : false;

            $('.department').removeClass('block');
            $('.teams').removeClass('block');
            $('.departmentBubble').removeClass('block');
            $('.teamsBubble').removeClass('block');
        }

        @if(Auth::user()->tariff === '1' && Auth::user()->password !== 'user' && Auth::user()->company_title !== null)
            async function launchChartLoading() {
                charts();
                // unBlockCharts();
                // gapReportDefault();
                // await qualtrics();
            }

            launchChartLoading();
        @endif

        // function blockCharts() {
        //     document.querySelector(".companyBubble") !== null ? document.querySelector(".companyBubble").disabled = true : false;
        //     document.querySelector(".departmentBubble") !== null ? document.querySelector(".departmentBubble").disabled = true : false;
        //     document.querySelector(".teamsBubble") !== null ? document.querySelector(".teamsBubble").disabled = true : false;
        //     document.querySelector(".company") !== null ? document.querySelector(".company").disabled = true : false;
        //     document.querySelector(".department") !== null ? document.querySelector(".department").disabled = true : false;
        //     document.querySelector(".teams") !== null ? document.querySelector(".teams").disabled = true : false;
        //
        //     if(sessionStorage.getItem("qualtrics") !== null) {
        //         document.querySelector(".department") !== null ? document.querySelector(".department").classList.add('block') : false;
        //         document.querySelector(".teams") !== null ? document.querySelector(".teams").classList.add('block') : false;
        //         document.querySelector(".departmentBubble") !== null ? document.querySelector(".departmentBubble").classList.add('block') : false;
        //         document.querySelector(".teamsBubble") !== null ? document.querySelector(".teamsBubble").classList.add('block') : false;
        //
        //         document.querySelector(".loader-btn-support-department") !== null ? document.querySelector(".loader-btn-support-department").style.display = "flex" : false;
        //         document.querySelector(".loader-btn-support-teams") !== null ? document.querySelector(".loader-btn-support-teams").style.display = "flex" : false;
        //         document.querySelector(".loader-btn-support-departmentBubble") !== null ? document.querySelector(".loader-btn-support-departmentBubble").style.display = "flex" : false;
        //         document.querySelector(".loader-btn-support-teamsBubble") !== null ? document.querySelector(".loader-btn-support-teamsBubble").style.display = "flex" : false;
        //     }
        // }
        //
        // function unBlockCharts() {
        //     $('#loaderIndicator') !== null ? $('#loaderIndicator').fadeOut() : false;
        //     $('#loaderTeam') !== null ? $('#loaderTeam').fadeOut() : false;
        //     $('#loaderITemperature') !== null ? $('#loaderITemperature').fadeOut() : false;
        //     $('#loaderGapReport') !== null ? $('#loaderGapReport').fadeOut() : false;
        //
        //     document.querySelector(".companyBubble") !== null ? document.querySelector(".companyBubble").disabled = false : false;
        //     document.querySelector(".departmentBubble") !== null ? document.querySelector(".departmentBubble").disabled = false : false;
        //     document.querySelector(".teamsBubble") !== null ? document.querySelector(".teamsBubble").disabled = false : false;
        //     document.querySelector(".company") !== null ? document.querySelector(".company").disabled = false : false;
        //     document.querySelector(".department") !== null ? document.querySelector(".department").disabled = false : false;
        //     document.querySelector(".teams") !== null ? document.querySelector(".teams").disabled = false : false;
        //
        //     if(sessionStorage.getItem("qualtrics") !== null) {
        //         // document.querySelector(".department").style.background = "#ECECEC";
        //
        //         document.querySelector(".department") !== null ? document.querySelector(".department").classList.remove('block') : false;
        //         document.querySelector(".teams") !== null ? document.querySelector(".teams").classList.remove('block') : false;
        //         document.querySelector(".departmentBubble") !== null ? document.querySelector(".departmentBubble").classList.remove('block') : false;
        //         document.querySelector(".teamsBubble") !== null ? document.querySelector(".teamsBubble").classList.remove('block') : false;
        //
        //         document.querySelector(".loader-btn-support-department") !== null ? document.querySelector(".loader-btn-support-department").style.display = "none" : false;
        //         document.querySelector(".loader-btn-support-teams") !== null ? document.querySelector(".loader-btn-support-teams").style.display = "none" : false;
        //         document.querySelector(".loader-btn-support-departmentBubble") !== null ? document.querySelector(".loader-btn-support-departmentBubble").style.display = "none" : false;
        //         document.querySelector(".loader-btn-support-teamsBubble") !== null ? document.querySelector(".loader-btn-support-teamsBubble").style.display = "none" : false;
        //     }
        // }

        {{--async function qualtrics() {--}}
        {{--    var myHeaders = new Headers();--}}
        {{--    myHeaders.append("X-API-TOKEN", "{{env('QUALTRICS_API_TOKEN')}}");--}}
        {{--    myHeaders.append("Content-Type", "application/json");--}}

        {{--    var raw = JSON.stringify({--}}
        {{--        "format": "json",--}}
        {{--        "compress": false--}}
        {{--    });--}}

        {{--    var postRequestOptions = {--}}
        {{--        method: 'POST',--}}
        {{--        headers: myHeaders,--}}
        {{--        body: raw,--}}
        {{--        redirect: 'follow'--}}
        {{--    };--}}

        {{--    var getRequestOptions = {--}}
        {{--        method: 'GET',--}}
        {{--        headers: myHeaders,--}}
        {{--        redirect: 'follow'--}}
        {{--    };--}}

        {{--    try {--}}
        {{--        blockCharts();--}}
        {{--        var firstRequest = await fetch("https://sjc1.qualtrics.com/API/v3/surveys/SV_9FtECtejcxTGgL4/export-responses", postRequestOptions);--}}

        {{--        if(firstRequest.ok) {--}}
        {{--            var firstResponse = await firstRequest.json();--}}
        {{--            var progressId = firstResponse["result"]["progressId"];--}}

        {{--            setTimeout(async () => {--}}
        {{--                var secondRequest = await fetch("https://sjc1.qualtrics.com/API/v3/surveys/SV_9FtECtejcxTGgL4/export-responses/" + progressId, getRequestOptions);--}}

        {{--                if(secondRequest.ok) {--}}
        {{--                    var secondResponse = await secondRequest.json();--}}
        {{--                    var fileId = secondResponse["result"]["fileId"];--}}

        {{--                    setTimeout(async () => {--}}
        {{--                        var thirdRequest = await fetch("https://sjc1.qualtrics.com/API/v3/surveys/SV_9FtECtejcxTGgL4/export-responses/" + fileId + "/file", getRequestOptions);--}}

        {{--                        if(thirdRequest.ok) {--}}
        {{--                            unBlockCharts();--}}
        {{--                            var thirdResponse = await thirdRequest.json();--}}
        {{--                            await charts(thirdResponse["responses"]);--}}
        {{--                            sessionStorage.setItem("qualtrics", JSON.stringify(thirdResponse["responses"]))--}}
        {{--                        }--}}
        {{--                    }, 4000)--}}
        {{--                }--}}
        {{--            }, 4000)--}}
        {{--        }--}}
        {{--    } catch(error) {--}}
        {{--        toastr.options = {--}}
        {{--            "closeButton": false,--}}
        {{--            "debug": true,--}}
        {{--            "newestOnTop": false,--}}
        {{--            "progressBar": false,--}}
        {{--            "positionClass": "toast-top-center",--}}
        {{--            "preventDuplicates": false,--}}
        {{--            "onclick": null,--}}
        {{--            "showDuration": "300",--}}
        {{--            "hideDuration": "1000",--}}
        {{--            "timeOut": "5000",--}}
        {{--            "extendedTimeOut": "1000",--}}
        {{--            "showEasing": "swing",--}}
        {{--            "hideEasing": "linear",--}}
        {{--            "showMethod": "fadeIn",--}}
        {{--            "hideMethod": "fadeOut"--}}
        {{--        }--}}

        {{--        toastr["error"]("Something went wrong! Try it later!")--}}
        {{--        console.error(error)--}}
        {{--    }--}}
        {{--}--}}

        async function charts(qualtricsRequestResult=0) {
            $('.department').addClass('block');
            $('.teams').addClass('block');
            $('.departmentBubble').addClass('block');
            $('.teamsBubble').addClass('block');
            
            function getResultsSatisfactionIndicator(canvas, dataResults) {
                var getDataAboutUsers = [];
                if (dataResults !== null && dataResults.length !== 0) {
                    var allValues = [];
                    dataResults.forEach(dataResult => {
                        allValues.push(JSON.parse(dataResult))
                    })

                    allValues.forEach(el => {
                        var x = Math.round((el["values"]["QID3_1"] + el["values"]["QID4_1"] + el["values"]["QID12_1"] + el["values"]["QID55_1"] + el["values"]["QID60_1"] + el["values"]["QID54_1"]) / 6);
                        var y = Math.round((el["values"]["QID50_1"] + el["values"]["QID50_1"] + el["values"]["QID4_1"] + el["values"]["QID15_1"] + el["values"]["QID14_1"] + el["values"]["QID7_6"]) / 6);

                        if (!isNaN(x) && !isNaN(y) && el["values"]["QID62_TEXT"] !== undefined) {
                            let obj = {x: x, y: y, r: 10, label: el["values"]["QID62_TEXT"]};
                            getDataAboutUsers.push(obj);
                        }
                    })

                    satisfactionIndicator(canvas, getDataAboutUsers);
                } else {
                    var defaultData = [{x: 1, y: 1, r: 10, label: "None results"}];
                    satisfactionIndicator(canvas, defaultData);
                }
            }
            function satisfactionIndicator(canvas, data) {
                if (Chart.getChart(canvas)) {
                    Chart.getChart(canvas).destroy();

                }
                const bubbleData = {
                    datasets: [{
                        data: data,
                        backgroundColor: 'black',
                        borderColor: 'black',
                    }]
                };

                bubbleData.datasets.forEach(function (dataset) {
                    dataset.data.forEach(function (point) {
                        if (point.y === 0) {
                            point.y = 1;
                        }

                        if (point.x === 0) {
                            point.x = 1;
                        }

                        if (point.y === 10) {
                            point.y = 9;
                        }

                        if (point.x === 10) {
                            point.x = 9;
                        }
                    });
                });

                var options = {
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.data[context.dataIndex].label;
                                }
                            },
                            displayColors: false
                        },
                    },
                    responsive: false,
                    scales: {
                        x: {
                            min: 0,
                            max: 10,
                            display: false
                        },
                        y: {
                            min: 0,
                            max: 10,
                            display: false
                        }
                    }
                };

                new Chart(canvas, {
                    type: 'bubble',
                    data: bubbleData,
                    options: options
                });
            }
            function satisfactionIndicatorShow(p) {
                @if(Auth::user()->role == 1)
                    const canvas = document.getElementById("bubble-company").getContext("2d");
                    const canvasModal = document.getElementById("bubble-company-modal").getContext("2d");
                @elseif(Auth::user()->role == 2)
                    const canvas = document.getElementById("bubble-department").getContext("2d");
                    const canvasModal = document.getElementById("bubble-department-modal").getContext("2d");
                @elseif(Auth::user()->role == 3)
                    const canvas = document.getElementById("bubble-team").getContext("2d");
                    const canvasModal = document.getElementById("bubble-team-modal").getContext("2d");
                @endif

                getResultsSatisfactionIndicator(canvas, p);
            }
            function getResultsTeamsChart(canvas, dataResults) {
                var getDataAboutUsers = [];

                if (dataResults !== null && dataResults.length !== 0) {
                    var allValues = [];
                    dataResults.forEach(dataResult => {
                        allValues.push(JSON.parse(dataResult))
                    })

                    allValues.forEach(el => {
                        var first = (el["values"]["QID7_1"] + el["values"]["QID1_1"] + el["values"]["QID1_2"] + el["values"]["QID1_3"] + el["values"]["QID2_1"] + el["values"]["QID2_2"] + el["values"]["QID2_3"] + el["values"]["QID2_4"] + el["values"]["QID3_1"] + el["values"]["QID3_2"] + el["values"]["QID3_3"] + el["values"]["QID3_4"] + el["values"]["QID4_1"]) * -1;

                        var second = (el["values"]["QID7_2"] + el["values"]["QID7_1"] + el["values"]["QID1_1"] + el["values"]["QID1_2"] + el["values"]["QID1_3"] + el["values"]["QID2_1"] + el["values"]["QID2_2"] + el["values"]["QID2_3"] + el["values"]["QID2_4"] + el["values"]["QID3_1"] + el["values"]["QID3_2"] + el["values"]["QID3_3"] + el["values"]["QID3_4"] + el["values"]["QID4_1"]) * -1;

                        var third = el["values"]["QID7_6"] + el["values"]["QID8_1"] + el["values"]["QID8_2"] + el["values"]["QID8_6"] + el["values"]["QID8_3"] + el["values"]["QID8_7"] + el["values"]["QID8_8"] + el["values"]["QID8_9"] + el["values"]["QID9_1"] + el["values"]["QID9_2"] + el["values"]["QID9_6"] + el["values"]["QID10_1"] + el["values"]["QID10_2"] + el["values"]["QID10_3"] + el["values"]["QID11_1"] + el["values"]["QID11_2"] + el["values"]["QID11_3"] + el["values"]["QID15_1"] + el["values"]["QID18_1"] + el["values"]["QID21_1"] + el["values"]["QID24_1"] + el["values"]["QID27_1"] + el["values"]["QID35_1"] + el["values"]["QID38_1"] + el["values"]["QID41_1"] + el["values"]["QID44_1"] + el["values"]["QID49_1"] + el["values"]["QID52_1"] + el["values"]["QID55_1"] + el["values"]["QID60_1"];

                        var xy = second + first + third;

                        if(el["values"]["QID62_TEXT"] !== undefined) {
                            if(!isNaN(xy)) {
                                var obj = {x: xy, y: xy, r: 12, label: el["values"]["QID62_TEXT"]};
                                getDataAboutUsers.push(obj);
                            } else {
                                var obj = {x: -9, y: -9, r: 12, label: el["values"]["QID62_TEXT"]};
                                getDataAboutUsers.push(obj);
                            }
                        }
                    })
                    teamsChart(canvas, getDataAboutUsers);
                } else {
                    var defaultData = [{x: -9, y: -9, r: 12, label: "None results"}];
                    teamsChart(canvas, defaultData);
                }
            }
            function teamsChart(canvas, data) {

                if (Chart.getChart(canvas)) {
                    Chart.getChart(canvas).destroy();
                }

                const bubbleData = {
                    datasets: [
                        {
                            data: data,
                            backgroundColor: "white",
                            borderColor: "white",
                            hoverBackgroundColor: "black"
                        }
                    ]
                };

                bubbleData.datasets.forEach(function (dataset) {
                    dataset.data.forEach(function (point) {
                        if (point.x === -10 || point.x < -10) {
                            point.x = -9;
                        } else if (point.x === 10 || point.x > 10) {
                            point.x = 9
                        }

                        if (point.y === -10 || point.y < -10) {
                            point.y = -9;
                        } else if (point.y === 10 || point.y > 10) {
                            point.y = 9
                        }
                    })
                })

                let options = {
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.data[context.dataIndex].label;
                                }
                            },
                            displayColors: false
                        }
                    },
                    responsive: false,
                    scales: {
                        x: {
                            min: -10,
                            max: 10,
                            display: false
                        },
                        y: {
                            min: -10,
                            max: 10,
                            display: false
                        }
                    }
                };

                new Chart(canvas, {
                    type: "bubble",
                    data: bubbleData,
                    options: options
                })
            }
            function teamsChartShow(p) {
                const canvas = document.getElementById("teamsChart").getContext("2d");
                getResultsTeamsChart(canvas, p);
            }
            function getResultsSatisfactionITemperature(canvas, dataResults) {
                var getDataAboutUsers = [];

                var commonDataKn = [0, 0];
                var commonDataCl = [0, 0];
                var commonDataTm = [0, 0];
                var commonDataSk = [0, 0];
                var commonDataMt = [0, 0];
                var commonDataLd = [0, 0];
                var commonDataOrg = [0, 0];
                var commonDataSc = [0, 0];
                var commonDataPj = [0, 0];
                var commonDataCul = [0, 0];
                var commonDataCh = [0, 0];

                if (dataResults !== null && dataResults.length !== 0) {
                    var allValues = [];
                    dataResults.forEach(dataResult => {
                        allValues.push(JSON.parse(dataResult))
                    })

                    allValues.forEach(el => {
                        let knFirst = (el["values"]["QID1_2"] - el["values"]["QID1_1"] < 0 || isNaN(el["values"]["QID1_2"] - el["values"]["QID1_1"])) ? 1 : el["values"]["QID1_2"] - el["values"]["QID1_1"];
                        let clFirst = (el["values"]["QID2_2"] - el["values"]["QID2_1"] < 0 || isNaN(el["values"]["QID2_2"] - el["values"]["QID2_1"])) ? 1 : el["values"]["QID2_2"] - el["values"]["QID2_1"];
                        let tmFirst = (el["values"]["QID3_2"] - el["values"]["QID3_1"] < 0 || isNaN(el["values"]["QID3_2"] - el["values"]["QID3_1"])) ? 1 : el["values"]["QID3_2"] - el["values"]["QID3_1"];
                        let skFirst = (el["values"]["QID7_2"] - el["values"]["QID7_1"] < 0 || isNaN(el["values"]["QID7_2"] - el["values"]["QID7_1"])) ? 1 : el["values"]["QID7_2"] - el["values"]["QID7_1"];
                        let mtFirst = (el["values"]["QID8_2"] - el["values"]["QID8_1"] < 0 || isNaN(el["values"]["QID8_2"] - el["values"]["QID8_1"])) ? 1 : el["values"]["QID8_2"] - el["values"]["QID8_1"];
                        let ldFirst = (el["values"]["QID9_2"] - el["values"]["QID9_1"] < 0 || isNaN(el["values"]["QID9_2"] - el["values"]["QID9_1"])) ? 1 : el["values"]["QID9_2"] - el["values"]["QID9_1"];
                        let orgFirst = (el["values"]["QID10_2"] - el["values"]["QID10_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID10_2"] - el["values"]["QID10_1"];
                        let scFirst = (el["values"]["QID11_2"] - el["values"]["QID11_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID11_2"] - el["values"]["QID11_1"];
                        let pjFirst = el["values"]["QID30_1"];
                        let culFirst = el["values"]["QID31_1"];
                        let chFirst = el["values"]["QID32_1"];

                        let knSecond = (el["values"]["QID1_3"] - 4 < 0 || isNaN(el["values"]["QID1_3"] - 4)) ? 1 : el["values"]["QID1_3"] - 4;
                        let clSecond = (el["values"]["QID2_3"] - 4 < 0 || isNaN(el["values"]["QID2_3"] - 4)) ? 1 : el["values"]["QID2_3"] - 4;
                        let tmSecond = (el["values"]["QID3_3"] - 4 < 0 || isNaN(el["values"]["QID3_3"] - 4)) ? 1 : el["values"]["QID3_3"] - 4;
                        let skSecond = (el["values"]["QID7_3"] - 4 < 0 || isNaN(el["values"]["QID7_3"] - 4)) ? 1 : el["values"]["QID7_3"] - 4;
                        let mtSecond = (el["values"]["QID8_3"] - 4 < 0 || isNaN(el["values"]["QID8_3"] - 4)) ? 1 : el["values"]["QID8_3"] - 4;
                        let ldSecond = (el["values"]["QID9_3"] - 4 < 0 || isNaN(el["values"]["QID9_3"] - 4)) ? 1 : el["values"]["QID9_3"] - 4;
                        let orgSecond = (el["values"]["QID10_3"] - 4 < 0 || isNaN(el["values"]["QID10_3"] - 4)) ? 1 : el["values"]["QID10_3"] - 4;
                        let scSecond = (el["values"]["QID11_3"] - 4 < 0 || isNaN(el["values"]["QID11_3"] - 4)) ? 1 : el["values"]["QID11_3"] - 4;
                        let pjSecond = (el["values"]["QID30_3"] - 4 < 0 || isNaN(el["values"]["QID30_3"] - 4)) ? 1 : el["values"]["QID30_3"] - 4;
                        let culSecond = (el["values"]["QID31_3"] - 4 < 0 || isNaN(el["values"]["QID31_3"] - 4)) ? 1 : el["values"]["QID31_3"] - 4;
                        let chSecond = (el["values"]["QID32_3"] - 4 < 0 || isNaN(el["values"]["QID32_3"] - 4)) ? 1 : el["values"]["QID32_3"] - 4;

                        commonDataKn[0] = commonDataKn[0] + knFirst;
                        commonDataKn[1] = commonDataKn[1] + knSecond;

                        commonDataCl[0] = commonDataCl[0] + clFirst;
                        commonDataCl[1] = commonDataCl[1] + clSecond;

                        commonDataTm[0] = commonDataTm[0] + tmFirst;
                        commonDataTm[1] = commonDataTm[1] + tmSecond;

                        commonDataSk[0] = commonDataSk[0] + skFirst;
                        commonDataSk[1] = commonDataSk[1] + skSecond;

                        commonDataMt[0] = commonDataMt[0] + mtFirst;
                        commonDataMt[1] = commonDataMt[1] + mtSecond;

                        commonDataLd[0] = commonDataLd[0] + ldFirst;
                        commonDataLd[1] = commonDataLd[1] + ldSecond;

                        commonDataOrg[0] = commonDataOrg[0] + orgFirst;
                        commonDataOrg[1] = commonDataOrg[1] + orgSecond;

                        commonDataSc[0] = commonDataSc[0] + scFirst;
                        commonDataSc[1] = commonDataSc[1] + scSecond;

                        commonDataPj[0] = commonDataPj[0] + pjFirst;
                        commonDataPj[1] = commonDataPj[1] + pjSecond;

                        commonDataCul[0] = commonDataCul[0] + culFirst;
                        commonDataCul[1] = commonDataCul[1] + culSecond;

                        commonDataCh[0] = commonDataCh[0] + chFirst;
                        commonDataCh[1] = commonDataCh[1] + chSecond;
                    })

                    function mainResult(f, s, name) {
                        let x = (f === null) ? 1 : f;
                        let y = (s === null) ? 1 : s;
                        getDataAboutUsers.push({x: x, y: y, r: 12, label: name})
                    }

                    mainResult(Math.round(commonDataKn[0] / dataResults.length), Math.round(commonDataKn[1] / dataResults.length), 'Knowledge Progress');
                    mainResult(Math.round(commonDataCl[0] / dataResults.length), Math.round(commonDataCl[1] / dataResults.length), 'Client Impact');
                    mainResult(Math.round(commonDataTm[0] / dataResults.length), Math.round(commonDataTm[1] / dataResults.length), 'Team Impact');
                    mainResult(Math.round(commonDataSk[0] / dataResults.length), Math.round(commonDataSk[1] / dataResults.length), 'Skill Progress');
                    mainResult(Math.round(commonDataMt[0] / dataResults.length), Math.round(commonDataMt[1] / dataResults.length), 'Material Progress - Pay & Benefits');
                    mainResult(Math.round(commonDataLd[0] / dataResults.length), Math.round(commonDataLd[1] / dataResults.length), 'Team & Leadership Ethics');
                    mainResult(Math.round(commonDataOrg[0] / dataResults.length), Math.round(commonDataOrg[1] / dataResults.length), 'Organization Impact');
                    mainResult(Math.round(commonDataSc[0] / dataResults.length), Math.round(commonDataSc[1] / dataResults.length), 'Societal Impact Size');
                    mainResult(Math.round(commonDataPj[0] / dataResults.length), Math.round(commonDataPj[1] / dataResults.length), 'Project Impact');
                    mainResult(Math.round(commonDataCul[0] / dataResults.length), Math.round(commonDataCul[1] / dataResults.length), 'Organization Culture');
                    mainResult(Math.round(commonDataCh[0] / dataResults.length), Math.round(commonDataCh[1] / dataResults.length), 'Character Culture');

                    satisfactionITemperature(canvas, getDataAboutUsers);
                } else {
                    satisfactionITemperature(canvas, [{x: 1, y: 1, r: 12, label: 'None results'}]);
                }
            }
            function satisfactionITemperature(canvas, data) {
                if (Chart.getChart(canvas)) {
                    Chart.getChart(canvas).destroy();
                }

                const bubbleData = {
                    datasets: [
                        {
                            data: data,
                            backgroundColor: "white",
                            borderColor: "white",
                            hoverBackgroundColor: "black"
                        }
                    ]
                };

                bubbleData.datasets.forEach(function (dataset) {
                    dataset.data.forEach(function (point) {
                        if (point.x === 0) {
                            point.x = 1;
                        } else if (point.x === 10) {
                            point.x = 9
                        }

                        if (point.y === 0) {
                            point.y = 1;
                        } else if (point.y === 10) {
                            point.y = 9
                        }
                    })
                })

                let options = {
                    plugins: {
                        legend: {
                            display: false,
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.data[context.dataIndex].label;
                                }
                            },
                            displayColors: false,
                            padding: 2,
                            bodyFont: {
                                size: 10
                            }
                        }
                    },
                    responsive: false,
                    scales: {
                        x: {
                            min: 0,
                            max: 10,
                            display: false
                        },
                        y: {
                            min: 0,
                            max: 10,
                            display: false
                        }
                    }
                };

                new Chart(canvas, {
                    type: "bubble",
                    data: bubbleData,
                    options: options
                })
            }
            function satisfactionITemperatureShow(p) {
                @if(Auth::user()->role == 1)
                    const canvas = document.getElementById("satisfaction-company").getContext("2d");
                @elseif(Auth::user()->role == 2)
                    const canvas = document.getElementById("satisfaction-depatment").getContext("2d");
                @elseif(Auth::user()->role == 3)
                    const canvas = document.getElementById("satisfaction-team").getContext("2d");
                @endif

                getResultsSatisfactionITemperature(canvas, p)
            }
            function getResultsGapReport(canvas, dataResults) {
                var getDataAboutUsers = [];

                var commonDataKn = [0, 0];
                var commonDataCl = [0, 0];
                var commonDataTm = [0, 0];
                var commonDataSk = [0, 0];
                var commonDataMt = [0, 0];
                var commonDataLd = [0, 0];
                var commonDataOrg = [0, 0];
                var commonDataSc = [0, 0];
                var commonDataPj = [0, 0];
                var commonDataCul = [0, 0];
                var commonDataCh = [0, 0];

                if (dataResults !== null && dataResults.length !== 0) {
                    var allValues = [];
                    dataResults.forEach(dataResult => {
                        allValues.push(JSON.parse(dataResult))
                    })

                    allValues.forEach(el => {
                        let knFirst = (el["values"]["QID1_2"] - el["values"]["QID1_1"] < 0 || isNaN(el["values"]["QID1_2"] - el["values"]["QID1_1"])) ? 1 : el["values"]["QID1_2"] - el["values"]["QID1_1"];
                        let clFirst = (el["values"]["QID2_2"] - el["values"]["QID2_1"] < 0 || isNaN(el["values"]["QID2_2"] - el["values"]["QID2_1"])) ? 1 : el["values"]["QID2_2"] - el["values"]["QID2_1"];
                        let tmFirst = (el["values"]["QID3_2"] - el["values"]["QID3_1"] < 0 || isNaN(el["values"]["QID3_2"] - el["values"]["QID3_1"])) ? 1 : el["values"]["QID3_2"] - el["values"]["QID3_1"];
                        let skFirst = (el["values"]["QID7_2"] - el["values"]["QID7_1"] < 0 || isNaN(el["values"]["QID7_2"] - el["values"]["QID7_1"])) ? 1 : el["values"]["QID7_2"] - el["values"]["QID7_1"];
                        let mtFirst = (el["values"]["QID8_2"] - el["values"]["QID8_1"] < 0 || isNaN(el["values"]["QID8_2"] - el["values"]["QID8_1"])) ? 1 : el["values"]["QID8_2"] - el["values"]["QID8_1"];
                        let ldFirst = (el["values"]["QID9_2"] - el["values"]["QID9_1"] < 0 || isNaN(el["values"]["QID9_2"] - el["values"]["QID9_1"])) ? 1 : el["values"]["QID9_2"] - el["values"]["QID9_1"];
                        let orgFirst = (el["values"]["QID10_2"] - el["values"]["QID10_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID10_2"] - el["values"]["QID10_1"];
                        let scFirst = (el["values"]["QID11_2"] - el["values"]["QID11_1"] < 0 || isNaN(el["values"]["QID10_2"] - el["values"]["QID10_1"])) ? 1 : el["values"]["QID11_2"] - el["values"]["QID11_1"];
                        let pjFirst = el["values"]["QID30_1"];
                        let culFirst = el["values"]["QID31_1"];
                        let chFirst = el["values"]["QID32_1"];

                        let knSecond = (el["values"]["QID1_3"] - 4 < 0 || isNaN(el["values"]["QID1_3"] - 4)) ? 1 : el["values"]["QID1_3"] - 4;
                        let clSecond = (el["values"]["QID2_3"] - 4 < 0 || isNaN(el["values"]["QID2_3"] - 4)) ? 1 : el["values"]["QID2_3"] - 4;
                        let tmSecond = (el["values"]["QID3_3"] - 4 < 0 || isNaN(el["values"]["QID3_3"] - 4)) ? 1 : el["values"]["QID3_3"] - 4;
                        let skSecond = (el["values"]["QID7_3"] - 4 < 0 || isNaN(el["values"]["QID7_3"] - 4)) ? 1 : el["values"]["QID7_3"] - 4;
                        let mtSecond = (el["values"]["QID8_3"] - 4 < 0 || isNaN(el["values"]["QID8_3"] - 4)) ? 1 : el["values"]["QID8_3"] - 4;
                        let ldSecond = (el["values"]["QID9_3"] - 4 < 0 || isNaN(el["values"]["QID9_3"] - 4)) ? 1 : el["values"]["QID9_3"] - 4;
                        let orgSecond = (el["values"]["QID10_3"] - 4 < 0 || isNaN(el["values"]["QID10_3"] - 4)) ? 1 : el["values"]["QID10_3"] - 4;
                        let scSecond = (el["values"]["QID11_3"] - 4 < 0 || isNaN(el["values"]["QID11_3"] - 4)) ? 1 : el["values"]["QID11_3"] - 4;
                        let pjSecond = (el["values"]["QID30_3"] - 4 < 0 || isNaN(el["values"]["QID30_3"] - 4)) ? 1 : el["values"]["QID30_3"] - 4;
                        let culSecond = (el["values"]["QID31_3"] - 4 < 0 || isNaN(el["values"]["QID31_3"] - 4)) ? 1 : el["values"]["QID31_3"] - 4;
                        let chSecond = (el["values"]["QID32_3"] - 4 < 0 || isNaN(el["values"]["QID32_3"] - 4)) ? 1 : el["values"]["QID32_3"] - 4;

                        commonDataKn[0] = commonDataKn[0] + knFirst;
                        commonDataKn[1] = commonDataKn[1] + knSecond;

                        commonDataCl[0] = commonDataCl[0] + clFirst;
                        commonDataCl[1] = commonDataCl[1] + clSecond;

                        commonDataTm[0] = commonDataTm[0] + tmFirst;
                        commonDataTm[1] = commonDataTm[1] + tmSecond;

                        commonDataSk[0] = commonDataSk[0] + skFirst;
                        commonDataSk[1] = commonDataSk[1] + skSecond;

                        commonDataMt[0] = commonDataMt[0] + mtFirst;
                        commonDataMt[1] = commonDataMt[1] + mtSecond;

                        commonDataLd[0] = commonDataLd[0] + ldFirst;
                        commonDataLd[1] = commonDataLd[1] + ldSecond;

                        commonDataOrg[0] = commonDataOrg[0] + orgFirst;
                        commonDataOrg[1] = commonDataOrg[1] + orgSecond;

                        commonDataSc[0] = commonDataSc[0] + scFirst;
                        commonDataSc[1] = commonDataSc[1] + scSecond;

                        commonDataPj[0] = commonDataPj[0] + pjFirst;
                        commonDataPj[1] = commonDataPj[1] + pjSecond;

                        commonDataCul[0] = commonDataCul[0] + culFirst;
                        commonDataCul[1] = commonDataCul[1] + culSecond;

                        commonDataCh[0] = commonDataCh[0] + chFirst;
                        commonDataCh[1] = commonDataCh[1] + chSecond;
                    })

                    function mainResult(f, s, name) {
                        let r = (isNaN(f * s)) ? 1 : f * s;
                        getDataAboutUsers.push([r, name])
                    }

                    mainResult(Math.round(commonDataKn[0] / dataResults.length), Math.round(commonDataKn[1] / dataResults.length), 'Knowledge Progress');
                    mainResult(Math.round(commonDataCl[0] / dataResults.length), Math.round(commonDataCl[1] / dataResults.length), 'Client Impact');
                    mainResult(Math.round(commonDataTm[0] / dataResults.length), Math.round(commonDataTm[1] / dataResults.length), 'Team Impact');
                    mainResult(Math.round(commonDataSk[0] / dataResults.length), Math.round(commonDataSk[1] / dataResults.length), 'Skill Progress');
                    mainResult(Math.round(commonDataMt[0] / dataResults.length), Math.round(commonDataMt[1] / dataResults.length), 'Material Progress - Pay & Benefits');
                    mainResult(Math.round(commonDataLd[0] / dataResults.length), Math.round(commonDataLd[1] / dataResults.length), 'Team & Leadership Ethics');
                    mainResult(Math.round(commonDataOrg[0] / dataResults.length), Math.round(commonDataOrg[1] / dataResults.length), 'Organization Impact');
                    mainResult(Math.round(commonDataSc[0] / dataResults.length), Math.round(commonDataSc[1] / dataResults.length), 'Societal Impact Size');
                    mainResult(Math.round(commonDataPj[0] / dataResults.length), Math.round(commonDataPj[1] / dataResults.length), 'Project Impact');
                    mainResult(Math.round(commonDataCul[0] / dataResults.length), Math.round(commonDataCul[1] / dataResults.length), 'Organization Culture');
                    mainResult(Math.round(commonDataCh[0] / dataResults.length), Math.round(commonDataCh[1] / dataResults.length), 'Character Culture');

                    getDataAboutUsers.sort((a, b) => b[0] - a[0]);
                    gapReport(canvas, getDataAboutUsers);
                }
            }
            function gapReport(canvas, getDataAboutUsers) {
                if (Chart.getChart(canvas)) {
                    Chart.getChart(canvas).destroy();
                }

                let ctx = canvas;
                let progressBars = [];
                let progressData = [];
                getDataAboutUsers.forEach(e => progressBars.push(e[1]));
                getDataAboutUsers.forEach(e => progressData.push(e[0]));
                progressData = progressData.map(value => (value === 0 ? 1 : value));
                let remainder = progressData.map(e => 10 - e);
                remainder.forEach((e, index) => {
                    if (e === 0) {
                        remainder[index] = '';
                    }
                });

                let data = {
                    labels: progressBars,
                    datasets: [
                        {
                            label: progressBars,
                            data: progressData,
                            backgroundColor: "#7B9BDE",
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.8,
                            categoryPercentage: 0.9,
                        },
                        {
                            label: progressBars,
                            data: remainder,
                            backgroundColor: "#E8E8E8",
                            borderColor: "#E8E8E8",
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.8,
                            categoryPercentage: 0.9
                        }
                    ],
                };

                let options = {
                    datasets: {
                        bar: {
                            borderSkipped: 'left',
                        }
                    },
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks:
                                {
                                    label: function (context) {
                                        return context.dataset.data[context.dataIndex].label;
                                    }
                                },
                            enabled: true,
                            displayColors: false,
                            position: 'nearest',
                            xAlign: 'right',
                            yAlign: 'bottom'
                        },
                        datalabels: {
                            font: {
                                size: 15,
                            },
                            align: "center"
                        }
                    },
                    scales: {
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            display: false,
                        },
                        x: {
                            stacked: true,
                            display: false,
                        }
                    },
                    responsive: false
                };

                let barWidthLine = {
                    id: "barWidthLine",
                    afterDatasetsDraw(chart, args, plugins) {
                        const {
                            ctx,
                            data,
                            chartArea: {top, bottom, width, height, left, right},
                            scales: {x, y}
                        } = chart;
                        const datasets = data.datasets[0].data;
                        const barThickness = height / 11 * data.datasets[0].barPercentage * data.datasets[0].categoryPercentage;
                        const half = barThickness / 2;

                        datasets.forEach((value, index) => {
                            if (value > 0) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.strokeStyle = "gray";
                                ctx.lineWidth = 1;
                                ctx.moveTo(x.getPixelForValue(value), y.getPixelForValue(index) - half);
                                ctx.lineTo(x.getPixelForValue(value), y.getPixelForValue(index) + half);
                                ctx.stroke();
                            } else if (value === 0) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.strokeStyle = "gray";
                                ctx.lineWidth = 1;
                                ctx.moveTo(x.getPixelForValue(1), y.getPixelForValue(index) - half);
                                ctx.lineTo(x.getPixelForValue(1), y.getPixelForValue(index) + half);
                                ctx.stroke();
                            }
                        });
                    }
                }

                new Chart(ctx, {
                    type: "bar",
                    data: data,
                    options: options,
                    plugins: [ChartDataLabels, barWidthLine],
                });
            }
            function gapReportShow(p) {
                const canvas = document.getElementById("gapReport").getContext("2d");
                getResultsGapReport(canvas, p);
            }

            gapReportDefault();

            if(qualtricsRequestResult === 0) {
                @if(isset($qualtrics))
                    @php $currentyTime = microtime(true) * 1000; @endphp
                    @if($currentyTime - $qualtrics->time < 300000)
                        satisfactionIndicatorShow({!! json_encode($qualtrics->data) !!});
                        teamsChartShow({!! json_encode($qualtrics->data) !!})
                        satisfactionITemperatureShow({!! json_encode($qualtrics->data) !!})
                        gapReportShow({!! json_encode($qualtrics->data) !!})
                    @else
                        const qualtricsRequest = await fetch("{{route('api.qualtrics')}}");
                        const qualtricsRequestResponse = await qualtricsRequest.json();
                        const qualtricsRequestResponseStatus = qualtricsRequestResponse['status'];
                        if (qualtricsRequestResponseStatus === 200) {
                            const qualtricsRequestResult = qualtricsRequestResponse['message']['data'];
                            qualtricsRequestResult.length === 0 ?
                                charts(null)
                                :
                                charts(qualtricsRequestResult);

                        }
                    @endif
                @endif
            } else {
                satisfactionIndicatorShow(qualtricsRequestResult);
                teamsChartShow(qualtricsRequestResult);
                satisfactionITemperatureShow(qualtricsRequestResult);
                gapReportShow(qualtricsRequestResult);
            }

            return unBlockCharts();
        }

        async function gapReportDefault() {
            function gapReportConst(canvas) {
                if(Chart.getChart(canvas)) {
                    Chart.getChart(canvas).destroy();
                }

                let ctx = canvas;
                let progressBars = ['Knowledge Progress',
                    'Client Impact',
                    'Team Impact',
                    'Skill Progress',
                    'Material Progress - Pay & Benefits',
                    'Team & Leadership Ethics',
                    'Organization Impact',
                    'Societal Impact Size',
                    'Project Impact',
                    'Organization Culture',
                    'Character Culture'];
                let progressData = [];
                while(progressData.length !== 11) { progressData.push(1) }
                let remainder = progressData.map(e => 10 - e);

                let data = {
                    labels: progressBars,
                    datasets: [
                        {
                            label: progressBars,
                            data: progressData,
                            backgroundColor: "#7B9BDE",
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.8,
                            categoryPercentage: 0.9,
                        },
                        {
                            label: progressBars,
                            data: remainder,
                            backgroundColor: "#E8E8E8",
                            borderColor: "#E8E8E8",
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.8,
                            categoryPercentage: 0.9
                        }
                    ],
                };

                let options = {
                    datasets: {
                        bar: {
                            borderSkipped: 'left',
                        }
                    },
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks:
                                {
                                    label: function(context) {
                                        return context.dataset.data[context.dataIndex].label;
                                    }
                                },
                            enabled: true,
                            displayColors: false,
                            position: 'nearest',
                            xAlign: 'right',
                            yAlign: 'bottom'
                        },
                        datalabels: {
                            formatter: (value, context) => {
                                if (context.dataset.backgroundColor === "#E8E8E8") {
                                    const index = context.dataIndex;
                                    const v = context.dataset.data[index];
                                    const l = context.dataset.label[index];

                                    return v;
                                }
                            },
                            font: {
                                size: 15,
                            },
                        }
                    },
                    scales: {
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            display: false,
                        },
                        x: {
                            stacked: true,
                            display: false,
                        }
                    },
                    responsive: false
                };

                let barWidthLine = {
                    id: "barWidthLine",
                    afterDatasetsDraw(chart, args, plugins) {
                        const {
                            ctx,
                            data,
                            chartArea: {top, bottom, width, height, left, right},
                            scales: {x, y}
                        } = chart;
                        const datasets = data.datasets[0].data;
                        const barThickness = height / 11 * data.datasets[0].barPercentage * data.datasets[0].categoryPercentage;
                        const half = barThickness / 2;

                        datasets.forEach((value, index) => {
                            if (value > 0) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.strokeStyle = "gray";
                                ctx.lineWidth = 1;
                                ctx.moveTo(x.getPixelForValue(value), y.getPixelForValue(index) - half);
                                ctx.lineTo(x.getPixelForValue(value), y.getPixelForValue(index) + half);
                                ctx.stroke();
                            } else if (value === 0) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.strokeStyle = "gray";
                                ctx.lineWidth = 1;
                                ctx.moveTo(x.getPixelForValue(1), y.getPixelForValue(index) - half);
                                ctx.lineTo(x.getPixelForValue(1), y.getPixelForValue(index) + half);
                                ctx.stroke();
                            }
                        });
                    }
                }

                new Chart(ctx, {
                    type: "bar",
                    data: data,
                    options: options,
                    plugins: [ChartDataLabels, barWidthLine],
                });
            }
            function gapReportShowConst() {
                let canvas = document.getElementById("gapReport").getContext("2d");
                let canvasModal = document.getElementById("gapReport-modal").getContext("2d");
                gapReportConst(canvas)
                gapReportConst(canvasModal)
            }

            (sessionStorage.getItem("gapReport") === null) ? gapReportShowConst() : "";
        }
    </script>

    <script src="{{asset('/js/home.js')}}" type="module"></script>
    <script src="{{asset('/js/modal.js')}}" type="module"></script>
@endsection
