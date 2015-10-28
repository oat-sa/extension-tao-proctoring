<div class="entries">
    <h1 {unless title}class="hidden"{/unless}>{{title}}</h1>
    <h2>
        <span class="empty-list {unless textEmpty}class="hidden"{/unless}">{{textEmpty}}</span>
        <span class="available-list {unless textNumber}class="hidden"{/unless}"><span class="label">{{textNumber}}</span>: <span class="count"></span></span>
        <span class="loading" {unless textLoading}class="hidden"{/unless}><span>{{textLoading}}</span>...</span>
    </h2>
    <div class="list"></div>
</div>
