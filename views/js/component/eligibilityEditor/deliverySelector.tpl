<span class="eligibility-delivery-selector">
    <label>{{__ "Eligible Delivery"}}</label>
    <select class="" data-has-search="false">
        <option></option>
        {{#each deliveries}}
        <option value="{{uri}}">{{label}}</option>
        {{/each}}
    </select>
</span>