@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb d-flex justify-content-between align-items-center">
            <div class="pull-left">
                <h2>Battery SOC Management</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success my-2" href="{{ route('battery_soc.create') }}" style="padding: 10px;"> Create New Battery SOC</a>
                <a class="btn btn-info my-2" href="{{ route('battery_soc.get-soc') }}" style="padding: 10px;">Get Current SOC</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <canvas id="socChart"></canvas>
            <div class="form-group">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="soc_plan_checkbox" value="soc_plan" checked>
                    <label class="form-check-label" for="soc_plan_checkbox">SOC Plan</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="soc_low_plan_checkbox" value="soc_low_plan">
                    <label class="form-check-label" for="soc_low_plan_checkbox">SOC Low Plan</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="current_checkbox" value="current" checked>
                    <label class="form-check-label" for="current_checkbox">Current</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="soc_forecast_checkbox" value="soc_forecast" checked>
                    <label class="form-check-label" for="soc_forecast_checkbox">SOC Forecast</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pv_yield_checkbox" value="pv_yield" checked>
                    <label class="form-check-label" for="pv_yield_checkbox">PV Yield</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pv_forecast_checkbox" value="pv_forecast" checked>
                    <label class="form-check-label" for="pv_forecast_checkbox">PV Forecast</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pv_min_target_checkbox" value="pv_min_target" checked>
                    <label class="form-check-label" for="pv_min_target_checkbox">PV Min Target</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pv_max_target_checkbox" value="pv_max_target" checked>
                    <label class="form-check-label" for="pv_max_target_checkbox">PV Max Target</label>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('battery_soc.index') }}" method="GET" class="my-3">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="soc_plans" {{ request('type') == 'soc_plans' ? 'selected' : '' }}>SOC Plan</option>
                        <option value="soc_low_plans" {{ request('type') == 'soc_low_plans' ? 'selected' : '' }}>SOC Low Plan</option>
                        <option value="current" {{ request('type') == 'current' ? 'selected' : '' }}>Current</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <tr>
            <th>Hour</th>
            <th>SOC</th>
            <th>Type</th>
            <th width="280px">Action</th>
        </tr>
        @foreach ($socData as $soc)
        <tr>
            <td>{{ $soc->hour }}</td>
            <td>{{ $soc->soc }}</td>
            <td>{{ $soc->type }}</td>
            <td>
                <form action="{{ route('battery_soc.destroy',$soc->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('battery_soc.show',$soc->id) }}">Show</a>
                    <a class="btn btn-primary" href="{{ route('battery_soc.edit',$soc->id) }}">Edit</a>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('socChart').getContext('2d');
        const chartData = @json($chartData);

        const labels = [...Array(17).keys()].map(i => i + 7).concat([...Array(7).keys()]);

        function formatData(data, labels) {
            if (!data) {
                return [];
            }
            const dataMap = new Map(Object.entries(data));
            return labels.map(label => dataMap.get(String(label)) || null);
        }

        const pvYieldKwhData = formatData(chartData.pv_yield_kwh, labels);
        const pvForecastKwhData = formatData(chartData.pv_forecast_kwh, labels);
        const pvMinTargetKwhData = formatData(chartData.pv_min_target_kwh, labels);
        const pvMaxTargetKwhData = formatData(chartData.pv_max_target_kwh, labels);
        const socForecastKwhData = formatData(chartData.soc_forecast_kwh, labels);

        const datasets = [
            {
                label: 'SOC Plan',
                data: formatData(chartData.soc_plans, labels),
                borderColor: 'rgba(190, 100, 230, 0.4)',
                backgroundColor: 'rgba(190, 100, 230, 0.1)',
                borderDash: [5, 5],
                hidden: !document.getElementById('soc_plan_checkbox').checked,
            },
            {
                label: 'SOC Low Plan',
                data: formatData(chartData.soc_low_plans, labels),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                hidden: !document.getElementById('soc_low_plan_checkbox').checked,
            },
            {
                label: 'Current',
                data: formatData(chartData.current, labels),
                borderColor: 'rgba(0, 100, 0, 1)',
                backgroundColor: 'rgba(0, 100, 0, 0.2)',
                hidden: !document.getElementById('current_checkbox').checked,
            },
            {
                label: 'SOC Forecast',
                data: formatData(chartData.soc_forecast, labels),
                borderColor: 'rgba(128, 255, 128, 1)',
                backgroundColor: 'rgba(128, 255, 128, 0.2)',
                hidden: !document.getElementById('soc_forecast_checkbox').checked,
            },
            {
                label: 'PV Yield',
                data: formatData(chartData.pv_yield, labels),
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                hidden: !document.getElementById('pv_yield_checkbox').checked,
            },
            {
                label: 'PV Forecast',
                data: formatData(chartData.pv_forecast, labels),
                borderColor: 'rgba(255, 200, 128, 1)',
                backgroundColor: 'rgba(255, 200, 128, 0.2)',
                hidden: !document.getElementById('pv_forecast_checkbox').checked,
            },
            {
                label: 'PV Min Target',
                data: formatData(chartData.pv_min_target, labels),
                borderColor: 'rgba(148, 0, 211, 0.4)',
                backgroundColor: 'rgba(148, 0, 211, 0.1)',
                borderDash: [5, 5],
                hidden: !document.getElementById('pv_min_target_checkbox').checked,
            },
            {
                label: 'PV Max Target',
                data: formatData(chartData.pv_max_target, labels),
                borderColor: 'rgba(148, 0, 211, 0.4)',
                backgroundColor: 'rgba(148, 0, 211, 0.1)',
                borderDash: [5, 5],
                hidden: !document.getElementById('pv_max_target_checkbox').checked,
            }
        ];

        const socChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(2) + ' %';
                                }

                                const datasetIndex = context.datasetIndex;
                                const dataIndex = context.dataIndex;

                                let kwhValue;
                                switch (datasetIndex) {
                                    case 3: // SOC Forecast
                                        kwhValue = socForecastKwhData[dataIndex];
                                        break;
                                    case 4: // PV Yield
                                        kwhValue = pvYieldKwhData[dataIndex];
                                        break;
                                    case 5: // PV Forecast
                                        kwhValue = pvForecastKwhData[dataIndex];
                                        break;
                                    case 6: // PV Min Target
                                        kwhValue = pvMinTargetKwhData[dataIndex];
                                        break;
                                    case 7: // PV Max Target
                                        kwhValue = pvMaxTargetKwhData[dataIndex];
                                        break;
                                }

                                if (kwhValue !== undefined && kwhValue !== null) {
                                    label += ' (' + parseFloat(kwhValue).toFixed(2) + ' kWh)';
                                }

                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'SOC (%)'
                        },
                        min: 0,
                        max: 100
                    }
                }
            }
        });

        document.getElementById('soc_plan_checkbox').addEventListener('change', function () {
            socChart.data.datasets[0].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('soc_low_plan_checkbox').addEventListener('change', function () {
            socChart.data.datasets[1].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('current_checkbox').addEventListener('change', function () {
            socChart.data.datasets[2].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('soc_forecast_checkbox').addEventListener('change', function () {
            socChart.data.datasets[3].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('pv_yield_checkbox').addEventListener('change', function () {
            socChart.data.datasets[4].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('pv_forecast_checkbox').addEventListener('change', function () {
            socChart.data.datasets[5].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('pv_min_target_checkbox').addEventListener('change', function () {
            socChart.data.datasets[6].hidden = !this.checked;
            socChart.update();
        });

        document.getElementById('pv_max_target_checkbox').addEventListener('change', function () {
            socChart.data.datasets[7].hidden = !this.checked;
            socChart.update();
        });
    });
</script>
@endsection
