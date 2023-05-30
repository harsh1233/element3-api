<table>
    <thead>
    <tr>
        <th align="center">Name</th>
        <th align="center">Format</th>
        <th align="center">Category</th>
        <th align="center">Sub Category</th>
    </tr>
    </thead>
    <tbody>
    @foreach($groups_data as $teaching_material)
        <tr>
            <td>{{ $teaching_material['name'] }} </td>
            <td>{{ $teaching_material['formate'] }}</td>
            <td>{{ $teaching_material['teaching_material_category_detail']['name'] }}</td>
            <td>{{ $teaching_material['teaching_material_sub_category_detail']['name'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
