<select name="filter[status]" class="filter status select2">
    <option value="" selected></option>
    {{#each statuses}}
    <option value="{{code}}">{{label}}</option>
    {{/each}}
</select>