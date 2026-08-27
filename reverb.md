C:\inetpub\wwwroot\promise-app\promise-management>%windir%\system32\inetsrv\appcmd list site
SITE "PromiseSSO" (id:1,bindings:http/*:80:,https/*:443:,state:Started)

C:\inetpub\wwwroot\promise-app\promise-management>%windir%\system32\inetsrv\appcmd list app
APP "PromiseSSO/" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/drw" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/inv" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/mng" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/npc" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/dash" (applicationPool:DefaultAppPool)
APP "PromiseSSO/app/admin" (applicationPool:DefaultAppPool)

C:\inetpub\wwwroot\promise-app\promise-management>%windir%\system32\inetsrv\appcmd list vdir
VDIR "PromiseSSO/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-portal-login\public)
VDIR "PromiseSSO/app" (physicalPath:C:\inetpub\wwwroot\promise-app)
VDIR "PromiseSSO/app/drw/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-drawing\public)
VDIR "PromiseSSO/app/inv/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-inventory\public)
VDIR "PromiseSSO/app/mng/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-management\public)
VDIR "PromiseSSO/app/npc/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-npc\public)
VDIR "PromiseSSO/app/dash/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-dashboard\public)
VDIR "PromiseSSO/app/admin/" (physicalPath:C:\inetpub\wwwroot\promise-app\promise-admin\public)

C:\inetpub\wwwroot\promise-app\promise-management>  

Nah, sekarang **sudah jelas penyebab utama konfigurasi sebelumnya salah**.

Struktur IIS Anda adalah:

```text
https://promise.summitadyawinsa.co.id/
│
├── Root
│   └── promise-portal-login
│
└── /app/
    │
    ├── /drw/
    ├── /inv/
    ├── /mng/   ← PROMISE MANAGEMENT
    ├── /npc/
    ├── /dash/
    └── /admin/
```

Project **Promise Management** Anda sebenarnya diakses melalui:

```text
https://promise.summitadyawinsa.co.id/app/mng/
```

Dan IIS Application-nya adalah:

```text
PromiseSSO/app/mng
```

Physical path:

```text
C:\inetpub\wwwroot\promise-app\promise-management\public
```

## Masalah utamanya

Frontend Anda mencoba konek ke:

```text
wss://promise.summitadyawinsa.co.id/app/key_lokal_bebas
```

Tetapi URL tersebut **bertabrakan dengan struktur IIS Anda**.

Karena IIS sudah memiliki Application `/app/...`, maka `/app/key_lokal_bebas` bukan endpoint khusus Reverb. IIS akan menganggap `/app` sebagai bagian dari struktur aplikasi Anda.

Jadi sebelumnya saya salah ketika menyarankan rule:

```text
^app/(.*)
```

Karena `/app` pada server Anda **sudah dipakai sebagai base path untuk semua aplikasi**.

Itulah sebabnya kita harus menggunakan endpoint WebSocket yang berbeda.

---

# Solusi yang saya rekomendasikan

Jangan gunakan endpoint default Reverb langsung pada root:

```text
/app/{REVERB_APP_KEY}
```

Karena URL `/app` sudah digunakan IIS.

Kita buat Reverb berada di path lain, misalnya:

```text
/reverb/app/{REVERB_APP_KEY}
```

Sehingga arsitekturnya:

```text
Browser
   │
   │ wss://promise.summitadyawinsa.co.id/reverb/app/key_lokal_bebas
   ▼
IIS
   │
   │ Reverse Proxy
   ▼
127.0.0.1:8080/app/key_lokal_bebas
   │
   ▼
Laravel Reverb
```

Dengan begitu tidak berbenturan dengan:

```text
/app/drw
/app/inv
/app/mng
/app/npc
/app/dash
/app/admin
```

---

# 1. Jangan ubah lagi `web.config` Promise Management

Untuk:

```text
C:\inetpub\wwwroot\promise-app\promise-management\public\web.config
```

Biarkan seperti semula.

Karena application `/app/mng` tidak menerima request:

```text
/reverb/...
```

Request tersebut masuk ke **root IIS site**, bukan ke aplikasi Laravel Management.

Jadi konfigurasi Reverb sebaiknya diletakkan di:

```text
C:\inetpub\wwwroot\promise-app\promise-portal-login\public\web.config
```

karena itu adalah root:

```text
PromiseSSO/
```

---

# 2. Tambahkan rule Reverb di root website

**Jangan replace seluruh `web.config` dulu.**

Di `web.config` milik:

```text
C:\inetpub\wwwroot\promise-app\promise-portal-login\public\
```

tambahkan rule berikut **di dalam `<rules>`**, sebelum rule Laravel root:

```xml
<rule name="Reverb WebSocket" stopProcessing="true">
    <match url="^reverb/app/(.*)" ignoreCase="false" />
    <action
        type="Rewrite"
        url="http://127.0.0.1:8080/app/{R:1}"
    />
</rule>

<rule name="Reverb API" stopProcessing="true">
    <match url="^reverb/apps/(.*)" ignoreCase="false" />
    <action
        type="Rewrite"
        url="http://127.0.0.1:8080/apps/{R:1}"
    />
</rule>
```

Dan tambahkan:

```xml
<webSocket enabled="true" />
```

di dalam:

```xml
<system.webServer>
```

Contoh struktur:

```xml
<configuration>
    <system.webServer>

        <webSocket enabled="true" />

        <rewrite>
            <rules>

                <clear />

                <!-- REVERB -->
                <rule name="Reverb WebSocket" stopProcessing="true">
                    <match url="^reverb/app/(.*)" ignoreCase="false" />
                    <action
                        type="Rewrite"
                        url="http://127.0.0.1:8080/app/{R:1}"
                    />
                </rule>

                <rule name="Reverb API" stopProcessing="true">
                    <match url="^reverb/apps/(.*)" ignoreCase="false" />
                    <action
                        type="Rewrite"
                        url="http://127.0.0.1:8080/apps/{R:1}"
                    />
                </rule>

                <!-- RULE LARAVEL ROOT YANG SUDAH ADA -->
                <!-- JANGAN DIUBAH -->

            </rules>
        </rewrite>

    </system.webServer>
</configuration>
```

**Penting:** saya sengaja tidak memberikan full `web.config` root karena saya belum melihat konfigurasi root yang sekarang. Jangan sampai kita mengulang masalah Forbidden tadi.

---

# 3. Masalah berikutnya: Laravel Echo perlu memakai path `/reverb`

Secara default Pusher/Reverb akan membentuk:

```text
/app/key_lokal_bebas
```

Kita perlu membuat Echo menggunakan:

```text
/reverb/app/key_lokal_bebas
```

Pada `resources/js/bootstrap.js` atau file tempat Echo dikonfigurasi:

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST,

    wsPort: Number(
        import.meta.env.VITE_REVERB_PORT ?? 80
    ),

    wssPort: Number(
        import.meta.env.VITE_REVERB_PORT ?? 443
    ),

    forceTLS: true,

    enabledTransports: ['ws', 'wss'],

    wsPath: '/reverb'
});
```

Namun saya ingin Anda cek dulu versi `laravel-echo` yang digunakan, karena penanganan path bergantung pada versi client dan konfigurasi transport.

---

# 4. Konfigurasi `.env` Management

Untuk aplikasi Management:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=123456
REVERB_APP_KEY=key_lokal_bebas
REVERB_APP_SECRET=secret_lokal_bebas

REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"

VITE_REVERB_HOST=promise.summitadyawinsa.co.id
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Yang sudah benar dari konfigurasi Anda adalah frontend memang harus mengarah ke:

```text
promise.summitadyawinsa.co.id:443
```

Sedangkan Reverb tetap:

```text
127.0.0.1:8080
```

---

# Gambaran akhir yang benar untuk server Anda

```text
Browser
   │
   │
   ├── https://promise.summitadyawinsa.co.id/
   │       ↓
   │   Promise Portal Login
   │
   ├── https://promise.summitadyawinsa.co.id/app/mng/
   │       ↓
   │   Promise Management
   │
   └── wss://promise.summitadyawinsa.co.id/reverb/app/key_lokal_bebas
           ↓
          IIS
           ↓
    Reverse Proxy
           ↓
    127.0.0.1:8080
           ↓
     Laravel Reverb
```

## Langkah berikutnya

Jangan ubah apa pun dulu. Kirimkan **isi `web.config` dari root website ini**:

```text
C:\inetpub\wwwroot\promise-app\promise-portal-login\public\web.config
```

dan kirim juga konfigurasi Echo Anda, yaitu bagian:

```javascript
new Echo({
    ...
})
```

Setelah melihat dua itu, saya bisa berikan perubahan yang **minimal**, hanya menambahkan rule Reverb tanpa merusak routing IIS `/app/mng`, `/app/inv`, dan aplikasi lain.
