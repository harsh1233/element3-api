<table>
    {{-- <thead> --}}
    <tr>
        <th align="center">Name</th>
    </tr>
    {{-- </thead> --}}
    <tbody>
    @foreach($language_data as $language)
        <tr>
            <td>{{ $language['name'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
