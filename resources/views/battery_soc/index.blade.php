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
                    <input class="form-check-input" type="checkbox" id="forecast_checkbox" value="forecast" checked>
                    <label class="form-check-label" for="forecast_checkbox">Forecast</label>
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

        const datasets = [
            {
                label: 'SOC Plan',
                data: formatData(chartData.soc_plans, labels),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                hidden: !document.getElementById('soc_plan_checkbox').checked
            },
            {
                label: 'SOC Low Plan',
                data: formatData(chartData.soc_low_plans, labels),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                hidden: !document.getElementById('soc_low_plan_checkbox').checked
            },
            {
                label: 'Current',
                data: formatData(chartData.current, labels),
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                hidden: !document.getElementById('current_checkbox').checked
            },
            {
                label: 'Forecast',
                data: formatData(chartData.forecast, labels),
                borderColor: 'rgba(255, 206, 86, 1)',
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                hidden: !document.getElementById('forecast_checkbox').checked
            }
        ];

        const socChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour'
                        }
                    },
                    y: {
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
        
        document.getElementById('forecast_checkbox').addEventListener('change', function () {
            socChart.data.datasets[3].hidden = !this.checked;
            socChart.update();
        });
    });
</script>
@endsection
