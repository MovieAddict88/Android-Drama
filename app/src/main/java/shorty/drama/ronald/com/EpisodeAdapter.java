package shorty.drama.ronald.com;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import java.util.List;

public class EpisodeAdapter extends RecyclerView.Adapter<EpisodeAdapter.ViewHolder> {
    private List<Episode> episodes;
    private OnEpisodeClickListener listener;

    public interface OnEpisodeClickListener {
        void onEpisodeClick(Episode episode);
    }

    public EpisodeAdapter(List<Episode> episodes, OnEpisodeClickListener listener) {
        this.episodes = episodes;
        this.listener = listener;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_episode, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Episode episode = episodes.get(position);
        holder.titleText.setText(episode.getChapterName());
        holder.indexText.setText("Episode " + episode.getChapterIndex());

        Glide.with(holder.itemView.getContext())
                .load(episode.getChapterImg())
                .placeholder(android.R.drawable.ic_menu_gallery)
                .into(holder.episodeImage);

        holder.itemView.setOnClickListener(v -> listener.onEpisodeClick(episode));
    }

    @Override
    public int getItemCount() {
        return episodes == null ? 0 : episodes.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView episodeImage;
        TextView titleText;
        TextView indexText;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            episodeImage = itemView.findViewById(R.id.episodeImage);
            titleText = itemView.findViewById(R.id.episodeTitle);
            indexText = itemView.findViewById(R.id.episodeIndex);
        }
    }
}
