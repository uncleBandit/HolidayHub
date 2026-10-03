@livewireScripts

<script>
    document.addEventListener("DOMContentLoaded", () => {
        Livewire.on('bookingConfirmed', ({ bookingId, message }) => {
            console.log("Booking confirmed:", bookingId, message);
        });
    });
</script>