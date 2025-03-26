class TorneoManager
{
    constructor()
    {
        this.initSelect2();
        this.bindEvents();
        this.initTooltips();
    }

    initSelect2()
    {
        $('#torneo_id').select2({
            placeholder: 'Seleccione un torneo',
            theme: 'bootstrap4'
        });
    }

    bindEvents()
    {
        $('#torneo_id').on('change', this.loadTorneoData.bind(this));
        $('#buscar_jugador').on('input', this.debouncedSearch.bind(this));
        $('#form_agregar').on('submit', this.addPlayer.bind(this));
        $('#iniciar_torneo').on('click', this.startTorneo.bind(this));
    }

    async loadTorneoData()
    {
        const torneoId = $('#torneo_id').val();
        if (!torneoId) return;

        try
        {
            this.showLoader();
            const [players, available] = await Promise.all([
                this.fetchPlayers(torneoId),
                this.fetchAvailablePlayers(torneoId)
            ]);

            this.updateUI(players, available);
        } catch (error)
        {
            this.showError('Error al cargar datos del torneo');
        } finally
        {
            this.hideLoader();
        }
    }

    async fetchPlayers(torneoId)
    {
        const response = await $.ajax({
            url: 'api/torneos.php',
            method: 'POST',
            data: { action: 'get_players', id: torneoId }
        });

        return response.data;
    }

    // Métodos restantes...
}

$(document).ready(() => new TorneoManager());