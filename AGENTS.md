# Consignes de continuité

<!-- KV2-PLESK-GZIP-BASE64:START -->
## Consigne commune Plesk SSH — 23 octobre 2026

Pour les scripts et blocs de commandes à coller dans le terminal SSH intégré Plesk, fournir **gzip + Base64 sur une seule ligne**, et non un bloc multiligne brut. C'est une demande explicite du propriétaire, applicable à tous les projets KV2 et prioritaire sur les anciennes consignes contraires de transport.

- Conserver la source lisible versionnée et expliquer l'action.
- Générer la charge mécaniquement ; vérifier décompression, identité avec la source et syntaxe.
- Mesurer la commande entière : cible pratique inférieure à 2 500 caractères. Si trop longue, découper ou installer un script versionné puis l'appeler brièvement.
- Avant une mutation, décompresser intégralement et valider avant exécution ; aucune exécution d'un flux incomplet.
- Si `^[[200~`, `bash~` ou un `~` parasite apparaît, Ctrl+C puis recoller la ligne complète proprement. La compression n'empêche pas ces parasites.
- Ne jamais encoder/versionner de secrets. Sans retour serveur, statut « à vérifier ».

[Procédure de référence](https://github.com/mrkv2/kv2-operations-playbook/blob/main/PLESK-SSH-GZIP-BASE64.md).
Cette règle concerne le transport Plesk ; les sources GitHub et les blocs à coller dans WordPress restent lisibles.
<!-- KV2-PLESK-GZIP-BASE64:END -->

