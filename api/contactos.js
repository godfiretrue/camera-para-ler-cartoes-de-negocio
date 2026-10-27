const { timingSafeEqual } = require("node:crypto");

const selectedColumns = "id,name,company,phone,email,type";
const contactTypes = ["Potencial Cliente", "Cliente", "Fornecedor"];

function sendJson(res, status, payload) {
    res.status(status).setHeader("Content-Type", "application/json; charset=utf-8").end(JSON.stringify(payload));
}

function getSupabaseConfig() {
    const url = process.env.SUPABASE_URL;
    const key = process.env.SUPABASE_SERVICE_ROLE_KEY;
    const password = process.env.APP_ACCESS_PASSWORD;
    const missingVariables = [
        ["SUPABASE_URL", url],
        ["SUPABASE_SERVICE_ROLE_KEY", key],
        ["APP_ACCESS_PASSWORD", password]
    ]
        .filter(([, value]) => typeof value !== "string" || value.trim() === "")
        .map(([name]) => name);

    if (missingVariables.length > 0) {
        throw new Error(`Vercel runtime is missing environment variable(s): ${missingVariables.join(", ")}.`);
    }

    let parsedUrl;
    try {
        parsedUrl = new URL(url);
    } catch {
        throw new Error("SUPABASE_URL is not a valid HTTPS project URL.");
    }
    if (parsedUrl.protocol !== "https:") {
        throw new Error("SUPABASE_URL must use HTTPS.");
    }

    return { url: parsedUrl.origin, key, password };
}

function validAccessPassword(candidate, expected) {
    const candidateBuffer = Buffer.from(candidate);
    const expectedBuffer = Buffer.from(expected);
    return candidateBuffer.length === expectedBuffer.length && timingSafeEqual(candidateBuffer, expectedBuffer);
}

function parseContact(body) {
    if (!body || typeof body !== "object" || Array.isArray(body)) {
        return { error: "O pedido deve conter um objeto JSON válido." };
    }

    const contact = {};
    for (const field of ["name", "company", "phone", "email"]) {
        const value = body[field] ?? "";
        if (typeof value !== "string") {
            return { error: "Todos os campos devem ser texto." };
        }
        contact[field] = value.trim();
        if (contact[field].length > 255) {
            return { error: "Cada campo pode ter no máximo 255 caracteres." };
        }
    }

    const type = body.type ?? "Potencial Cliente";
    if (typeof type !== "string" || !contactTypes.includes(type)) {
        return { error: "Selecione um tipo de contacto válido." };
    }
    contact.type = type;

    if (!contact.name) {
        return { error: "O nome é obrigatório." };
    }
    if (contact.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contact.email)) {
        return { error: "Introduza um endereço de e-mail válido." };
    }

    return { contact };
}

async function supabaseRequest(config, path, options = {}) {
    const response = await fetch(`${config.url}/rest/v1/${path}`, {
        ...options,
        headers: {
            apikey: config.key,
            Authorization: `Bearer ${config.key}`,
            "Content-Type": "application/json",
            ...options.headers
        }
    });
    const responseBody = await response.text();
    let payload = null;
    if (responseBody) {
        try {
            payload = JSON.parse(responseBody);
        } catch {
            throw new Error("Supabase returned an invalid response.");
        }
    }
    if (!response.ok) {
        console.error("Supabase request failed:", response.status, payload?.code || payload?.message || "Unknown error");
        const message = response.status >= 500
            ? "A base de dados Supabase está temporariamente indisponível."
            : payload?.message || "Não foi possível concluir a operação na base de dados.";
        const error = new Error(message);
        error.status = response.status >= 500 ? 502 : response.status;
        error.code = typeof payload?.code === "string" ? payload.code : null;
        throw error;
    }
    return payload;
}

module.exports = async function handler(req, res) {
    let config;
    try {
        config = getSupabaseConfig();
    } catch (error) {
        console.error(error.message);
        return sendJson(res, 500, { error: error.message });
    }

    const suppliedPassword = req.headers["x-app-password"];
    if (typeof suppliedPassword !== "string" || !validAccessPassword(suppliedPassword, config.password)) {
        return sendJson(res, 401, { error: "Acesso não autorizado." });
    }

    try {
        if (req.method === "GET") {
            const search = typeof req.query.q === "string" ? req.query.q.trim() : "";
            const params = new URLSearchParams({ select: selectedColumns, order: "name.asc,id.desc", limit: "1000" });
            const type = typeof req.query.type === "string" ? req.query.type : "";
            if (type && !contactTypes.includes(type)) {
                return sendJson(res, 400, { error: "Tipo de contacto inválido." });
            }
            if (type) params.set("type", `eq.${type}`);
            const escapedSearch = search.replace(/[\\%_*]/g, "\\$&");

            if (!search) {
                const contacts = await supabaseRequest(config, `contacts?${params}`);
                return sendJson(res, 200, { contacts });
            }

            const searchContacts = async (field) => {
                const fieldParams = new URLSearchParams(params);
                fieldParams.set(field, `ilike.*${escapedSearch}*`);
                return supabaseRequest(config, `contacts?${fieldParams}`);
            };
            const [nameMatches, companyMatches] = await Promise.all([
                searchContacts("name"),
                searchContacts("company")
            ]);
            const unique = new Map([...nameMatches, ...companyMatches].map((contact) => [contact.id, contact]));
            const contacts = [...unique.values()].sort((left, right) =>
                left.name.localeCompare(right.name, undefined, { sensitivity: "base" }) || right.id - left.id
            );
            return sendJson(res, 200, { contacts });
        }

        if (req.method === "POST") {
            const { contact, error } = parseContact(req.body);
            if (error) return sendJson(res, 422, { error });
            const contacts = await supabaseRequest(config, "contacts", {
                method: "POST",
                headers: { Prefer: "return=representation" },
                body: JSON.stringify(contact)
            });
            return sendJson(res, 201, { contact: contacts[0] });
        }

        const id = Number(req.query.id);
        if (!Number.isSafeInteger(id) || id < 1) {
            return sendJson(res, 400, { error: "Identificador de contacto inválido." });
        }

        if (req.method === "PUT") {
            const { contact, error } = parseContact(req.body);
            if (error) return sendJson(res, 422, { error });
            const params = new URLSearchParams({ id: `eq.${id}`, select: selectedColumns });
            const contacts = await supabaseRequest(config, `contacts?${params}`, {
                method: "PATCH",
                headers: { Prefer: "return=representation" },
                body: JSON.stringify({ ...contact, updated_at: new Date().toISOString() })
            });
            if (!contacts.length) return sendJson(res, 404, { error: "Contacto não encontrado." });
            return sendJson(res, 200, { contact: contacts[0] });
        }

        if (req.method === "DELETE") {
            const params = new URLSearchParams({ id: `eq.${id}`, select: "id" });
            const deleted = await supabaseRequest(config, `contacts?${params}`, {
                method: "DELETE",
                headers: { Prefer: "return=representation" }
            });
            if (!deleted.length) return sendJson(res, 404, { error: "Contacto não encontrado." });
            return sendJson(res, 200, { message: "Contacto eliminado." });
        }

        res.setHeader("Allow", "GET, POST, PUT, DELETE");
        return sendJson(res, 405, { error: "Método não permitido." });
    } catch (error) {
        console.error("Contacts API failed:", error.message);
        return sendJson(res, error.status || 500, {
            error: error.status
                ? error.code
                    ? `${error.message} (Supabase ${error.code}, HTTP ${error.status}).`
                    : error.message
                : "Não foi possível contactar o Supabase. Verifique SUPABASE_URL, a chave de servidor e os registos da função Vercel."
        });
    }
};
