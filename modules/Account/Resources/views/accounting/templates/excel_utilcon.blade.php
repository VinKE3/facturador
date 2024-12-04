<table>
    <tr>
        <td>ITEM</td>
        <td>EMISIÓN</td>
        <td>VENCIMIENTO</td>
        <td>TIPO</td>
        <td>NUMERO</td>
        <td>MONEDA</td>
        <td>CODIGO|RUC|DNI</td>
        <td>CLIENTE</td>
        <td>VALOR</td>
        <td>VENTA BOLSA</td>
        <td>EXONERADO</td>
        <td>IGV</td>
        <td>ICBPER</td>
        <td>PERCEPCIÓN</td>
        <td>TOTAL</td>
        <td>SUB</td>
        <td>COSTO</td>
        <td>CTACBLE</td>
        <td>GLOSA</td>
        <td>TDOC REF</td>
        <td>NUMERO REF</td>
        <td>FECHA REF</td>
        <td>IGV REF</td>
        <td>BASE IMP REF</td>
    </tr>
    @foreach($records as $row)
        <tr>
            <td>{{ $row["item"] }}</td>
            <td>{{ $row["fecha"] }}</td>
            <td>{{ $row["vencimiento"] }}</td>
            <td>{{ $row["tipo"] }}</td>
            <td>{{ $row["numero"] }}</td>
            <td>{{ $row["moneda"] }}</td>
            <td>{{ $row["cliente_numero_documento_identidad"] }}</td>
            <td>{{ $row["cliente_nombre"] }}</td>
            <td>{{ $row["valor"] }}</td>
            <td>{{ $row["venta_bolsa"] }}</td>
            <td>{{ $row["exonerado"] }}</td>
            <td>{{ $row["igv"] }}</td>
            <td>{{ $row["icbper"] }}</td>
            <td>{{ $row["percepcion"] }}</td>
            <td>{{ $row["total"] }}</td>
            <td>{{ $row["sub"] }}</td>
            <td>{{ $row["costo"] }}</td>
            <td>{{ $row["ctacble"] }}</td>
            <td>{{ $row["glosa"] }}</td>
            <td>{{ $row["tdoc_ref"] }}</td>
            <td>{{ $row["numero_ref"] }}</td>
            <td>{{ $row["fecha_ref"] }}</td>
            <td>{{ $row["igv_ref"] }}</td>
            <td>{{ $row["base_imp_ref"] }}</td>
        </tr>
    @endforeach
</table>