<div class="eligibility-editor">
    <h2 class="title">{{title}}</h2>
    <section class="eligible-delivery-select">
        <label>{{__ 'Eligible Deliveries'}}</label>
        <div>
            <div id="{{deliveryTreeId}}"></div>
        </div>
    </section>
    <section class="eligible-testTaker-tree-container">
        <label>{{__ 'Eligible Test Takers'}}</label>
        <div>
            <div id="{{subjectTreeId}}"></div>
        </div>
    </section>
    <div class="actions">
        <button class="btn btn-info small done">{{__ "OK"}}</button>
        <a href="#" class="btn cancel" title="{{__ "cancel the action"}}">{{__ "cancel"}}</a>
    </div>
</div>