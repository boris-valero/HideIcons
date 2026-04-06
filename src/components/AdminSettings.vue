<template>
	<form id="ghosticons-admin-settings" @submit.prevent="save">
		<table>
			<thead>
				<tr>
					<th>{{ t('ghosticons', 'Application name') }}</th>
					<th>{{ t('ghosticons', 'Do you want to hide the icon?') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="app in apps" :key="app.id">
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
const appsUrl = generateUrl('/apps/ghosticons/api/admin/apps')
const hiddenUrl = generateUrl('/apps/ghosticons/api/admin/hidden')

onMounted(async () => {
	try {
		const response = await axios.get(appsUrl)
		apps.value = response.data
	} catch (e) {
		showError(t('ghosticons', 'Loading error'))
	}
})

const save = async () => {
	try {
		const payload = new URLSearchParams()
		apps.value
			.filter(a => a.hidden)
			.forEach((a) => payload.append('hidden[]', a.id))

		await axios.post(hiddenUrl, payload, {
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
		width: 60%;
	}

	th:nth-child(2),
	td:nth-child(2) {
		width: 40%;
		text-align: center;
	}

	th {
		background-color: var(--color-background-hover);
		font-weight: bold;
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
