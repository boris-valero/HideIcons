<template>
	<form id="ghosticons-admin-settings" @submit.prevent="save">
		<p class="hint">
			{{ t('ghosticons', 'Drag and drop rows to define the top menu order.') }}
		</p>
		<table>
			<thead>
				<tr>
					<th>{{ t('ghosticons', 'Order') }}</th>
					<th>{{ t('ghosticons', 'Application name') }}</th>
					<th>{{ t('ghosticons', 'Do you want to hide the icon?') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="(app, index) in apps"
					:key="app.id"
					draggable="true"
					:class="{ dragging: draggedIndex === index }"
					@dragstart="onDragStart(index, $event)"
					@dragover="onDragOver($event)"
					@drop="onDrop(index, $event)"
					@dragend="onDragEnd">
					<td class="order-cell">
						<span class="drag-handle" :title="t('ghosticons', 'Drag to reorder')">:::</span>
						<span>{{ index + 1 }}</span>
					</td>
					<td>{{ app.name }}</td>
					<td>
						<NcCheckboxRadioSwitch
							v-model="app.hidden"
							:disabled="app.protected"
							:title="app.protected ? t('ghosticons', 'This application cannot be hidden') : ''" />
					</td>
				</tr>
			</tbody>
		</table>
		<div class="form-actions">
			<NcButton type="primary" native-type="submit">
				{{ t('ghosticons', 'Save') }}
			</NcButton>
		</div>
	</form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcCheckboxRadioSwitch, NcButton } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import '@nextcloud/dialogs/style.css'

const apps = ref([])
const draggedIndex = ref(null)
const appsUrl = generateUrl('/apps/ghosticons/api/admin/apps')
const preferencesUrl = generateUrl('/apps/ghosticons/api/admin/preferences')

onMounted(async () => {
	try {
		const response = await axios.get(appsUrl)
		apps.value = response.data
	} catch (e) {
		showError(t('ghosticons', 'Loading error'))
	}
})

const onDragStart = (index, event) => {
	draggedIndex.value = index
	event.dataTransfer.effectAllowed = 'move'
	event.dataTransfer.setData('text/plain', String(index))
}

const onDragOver = (event) => {
	event.preventDefault()
	event.dataTransfer.dropEffect = 'move'
}

const onDrop = (index, event) => {
	event.preventDefault()
	const dragged = draggedIndex.value ?? Number(event.dataTransfer.getData('text/plain'))

	if (!Number.isInteger(dragged) || dragged < 0 || dragged >= apps.value.length || dragged === index) {
		draggedIndex.value = null
		return
	}

	const [movedApp] = apps.value.splice(dragged, 1)
	apps.value.splice(index, 0, movedApp)
	draggedIndex.value = null
}

const onDragEnd = () => {
	draggedIndex.value = null
}

const save = async () => {
	try {
		const payload = new URLSearchParams()
		apps.value
			.filter(a => a.hidden)
			.forEach((a) => payload.append('hidden[]', a.id))
		apps.value.forEach((a) => payload.append('ordered[]', a.id))

		await axios.post(preferencesUrl, payload, {
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
		})
		showSuccess(t('ghosticons', 'Saved!'))
	} catch (e) {
		showError(t('ghosticons', 'Save error'))
	}
}
</script>

<style scoped lang="scss">
#ghosticons-admin-settings {
	max-width: 700px;

	.hint {
		margin-top: calc(var(--default-grid-baseline) * 3);
		color: var(--color-text-maxcontrast);
	}

	table {
		width: 100%;
		border-collapse: collapse;
		margin: calc(var(--default-grid-baseline) * 5) 0;
		table-layout: fixed;
	}

	th,
	td {
		padding: calc(var(--default-grid-baseline) * 4);
		text-align: left;
		border-bottom: var(--border-width-input) solid var(--color-border);
		vertical-align: middle;
	}

	th:nth-child(1),
	td:nth-child(1) {
		width: 18%;
	}

	th:nth-child(2),
	td:nth-child(2) {
		width: 42%;
	}

	th:nth-child(3),
	td:nth-child(3) {
		width: 40%;
		text-align: center;
	}

	th {
		background-color: var(--color-background-hover);
		font-weight: bold;
	}

	tbody tr {
		cursor: grab;
	}

	tbody tr.dragging {
		opacity: 0.5;
	}

	.order-cell {
		display: flex;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	.drag-handle {
		font-weight: bold;
		letter-spacing: 1px;
		color: var(--color-text-maxcontrast);
	}

	.form-actions {
		margin: calc(var(--default-grid-baseline) * 5) 0;
	}

	label {
		cursor: pointer;
		user-select: none;
	}
}
</style>
