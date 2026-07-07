<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Simulation de prêt</title>
  <style>
    * { box-sizing: border-box; }
    body {
      color: #2a2a3c;
      font-family: DejaVu Sans, sans-serif;
      font-size: 9px;
      margin: 0;
    }
    .band {
      height: 5px;
      background: linear-gradient(90deg, #6c4bc4 0 30%, #e8a33d 30% 55%, #17897b 55% 80%, #c2502c 80% 100%);
    }
    .header { padding: 18px 24px 6px; }
    h1 { color: #6c4bc4; font-size: 18px; margin: 0 0 2px; }
    .subtitle { color: #777; font-size: 10px; margin: 0; }
    .content { padding: 0 24px 24px; }

    .summary { margin-top: 12px; width: 100%; border-collapse: collapse; }
    .summary td {
      border: 1px solid #e2e0ef;
      padding: 6px 8px;
      width: 25%;
    }
    .summary .label { color: #777; font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; }
    .summary .value { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .value.accent { color: #6c4bc4; }

    h2 { font-size: 12px; margin: 18px 0 6px; }

    table.schedule { border-collapse: collapse; width: 100%; }
    .schedule th {
      background: #6c4bc4;
      color: #fff;
      font-size: 8px;
      padding: 5px 6px;
      text-align: right;
      text-transform: uppercase;
    }
    .schedule th:first-child, .schedule th:nth-child(2),
    .schedule td:first-child, .schedule td:nth-child(2) { text-align: left; }
    .schedule td {
      border-bottom: 1px solid #eceaf6;
      padding: 4px 6px;
      text-align: right;
    }
    .schedule tr:nth-child(even) td { background: #f7f6fc; }

    .footer { color: #999; font-size: 7.5px; margin-top: 16px; }
  </style>
</head>
<body>
  <div class="band"></div>
  <div class="header">
    <h1>Simulation de prêt — {{ config('app.name') }}</h1>
    <p class="subtitle">
      {{ $simulation->title ?: 'Tableau d\'amortissement' }} ·
      généré le {{ now()->format('d/m/Y') }}
    </p>
  </div>

  <div class="content">
    <table class="summary">
      <tr>
        <td>
          <div class="label">Montant emprunté</div>
          <div class="value">{{ number_format($params['amount'], 0, ',', ' ') }} FCFA</div>
        </td>
        <td>
          <div class="label">Taux annuel</div>
          <div class="value">{{ str_replace('.', ',', (string) $params['annual_rate']) }} %</div>
        </td>
        <td>
          <div class="label">Durée</div>
          <div class="value">{{ $params['duration_months'] }} mois</div>
        </td>
        <td>
          <div class="label">Méthode</div>
          <div class="value">{{ $methodLabel }}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Mensualité</div>
          <div class="value accent">{{ number_format($summary['monthly_payment'], 0, ',', ' ') }} FCFA</div>
        </td>
        <td>
          <div class="label">Total intérêts</div>
          <div class="value">{{ number_format($summary['total_interest'], 0, ',', ' ') }} FCFA</div>
        </td>
        <td>
          <div class="label">Coût total du crédit</div>
          <div class="value">{{ number_format($summary['total_cost'], 0, ',', ' ') }} FCFA</div>
        </td>
        <td>
          <div class="label">Total remboursé</div>
          <div class="value">{{ number_format($summary['total_paid'], 0, ',', ' ') }} FCFA</div>
        </td>
      </tr>
    </table>

    <h2>Tableau d'amortissement</h2>
    <table class="schedule">
      <thead>
        <tr>
          <th>N°</th>
          <th>Date</th>
          <th>Capital</th>
          <th>Intérêts</th>
          @if (($summary['total_insurance'] ?? 0) > 0)
            <th>Assurance</th>
          @endif
          <th>Mensualité</th>
          <th>Solde restant</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($schedule as $row)
          <tr>
            <td>{{ $row['period'] }}</td>
            <td>{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('M Y') }}</td>
            <td>{{ number_format($row['principal'], 0, ',', ' ') }}</td>
            <td>{{ number_format($row['interest'], 0, ',', ' ') }}</td>
            @if (($summary['total_insurance'] ?? 0) > 0)
              <td>{{ number_format($row['insurance'], 0, ',', ' ') }}</td>
            @endif
            <td><strong>{{ number_format($row['total'], 0, ',', ' ') }}</strong></td>
            <td>{{ number_format($row['balance'], 0, ',', ' ') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <p class="footer">
      Montants en francs CFA (XOF). Résultats fournis à titre indicatif — ne constitue pas une offre de crédit.
    </p>
  </div>
</body>
</html>
