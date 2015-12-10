<div class="eligibility-editor modal-form">
    <h2 class="title">{{title}}</h2>
    <section class="eligible-delivery-select"></section>
    <section class="eligible-testTaker-tree-container">
        <header>
            <h1><?= __('Eligible test takers') ?></h1>
        </header>
        <div>
            <div id="{{treeId}}"></div>
        </div>
        <footer>
        </footer>
    </section>
    <div class="actions">
        <button class="btn btn-info small done">{{__ "OK"}}</button>
        <a href="#" class="btn cancel" title="{{__ "cancel the action"}}">{{__ "cancel"}}</a>
    </div>
</div>